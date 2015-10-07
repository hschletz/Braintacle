###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory;

use strict;

BEGIN{
  if($ENV{'OCS_MODPERL_VERSION'} == 1){
    require Apache::Ocsinventory::Server::Modperl1;
    Apache::Ocsinventory::Server::Modperl1->import();
  }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
    require Apache::Ocsinventory::Server::Modperl2;
    Apache::Ocsinventory::Server::Modperl2->import();
  }else{
    if(!defined($ENV{'OCS_MODPERL_VERSION'})){
      die("OCS_MODPERL_VERSION not defined. Abort\n");
    }else{
      die("OCS_MODPERL_VERSION set to, a bad parameter. Must be '1' or '2'. Abort\n");
    }
  }
}

$Apache::Ocsinventory::VERSION = '2.1';
$Apache::Ocsinventory::BUILD_VERSION = '726';
$XML::Simple::PREFERRED_PARSER = 'XML::Parser';

# Ocs modules
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw /:server _modules_get_request_handler /;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Inventory;
use Apache::Ocsinventory::Server::Groups;
use Apache::Ocsinventory::Server::Useragent;
use Apache::Ocsinventory::Server::Capacities::Download;
use Apache::Ocsinventory::Server::Capacities::Ipdiscover;
use Apache::Ocsinventory::Server::Capacities::Notify;
use Apache::Ocsinventory::Server::Capacities::Registry;
use Apache::Ocsinventory::Server::Capacities::Snmp;
use Apache::Ocsinventory::Server::Capacities::Update;
use Apache::Ocsinventory::Interface::Database;

# To compress the tx and read the rx
use Compress::Zlib;
use Encode;

# Additional modules
use XML::Simple;
use Date::Calc qw(check_date);

# Globale structure
our %CURRENT_CONTEXT;
our @XMLParseOptForceArray;# Obsolete, for 1.01 modules only
my %XML_PARSER_OPT; 
our @TRUSTED_IP;

sub handler{
  my $d;
  my $status;
  my $r;
  my $data;
  my $raw_data;
  my $inflated;
  my $query;
  my $dbMode;

  # current context
  # Will be used to handle all globales
  %CURRENT_CONTEXT = (
    'APACHE_OBJECT' => undef,
    'RAW_DATA'  => undef,
    #'DBI_HANDLE'   => undef,
    # DBI_SL_HANDLE => undef
    'DEVICEID'   => undef,
    'DATABASE_ID'   => undef,
    'DATA'     => undef,
    'XML_ENTRY'   => undef,
    'XML_INVENTORY' => undef,
    'LOCK_FL'   => 0,
    'EXIST_FL'   => 0,
    'MEMBER_OF'   => undef,
    'DEFLATE_SUB'   => \&Compress::Zlib::compress,
    'IS_TRUSTED'  => 0,
    'DETAILS'  => undef,
    'PARAMS'  => undef,
    'PARAMS_G'  => undef,
    'MEMBER_OF'  => undef,
    'IPADDRESS'  => $ENV{'HTTP_X_FORWARDED_FOR'}?$ENV{'HTTP_X_FORWARDED_FOR'}:$ENV{'REMOTE_ADDR'},
    'USER_AGENT'  => undef,
    'LOCAL_FL' => undef
  );
  
  # No buffer for STDOUT
  select(STDOUT);
  $|=1;
  
  # Get the data and the apache object
  $r=shift;
  $CURRENT_CONTEXT{'APACHE_OBJECT'} = $r;
  
  $CURRENT_CONTEXT{'USER_AGENT'} = &_get_http_header('User-agent', $r);
  
  @TRUSTED_IP = $r->dir_config->get('OCS_OPT_TRUSTED_IP');
  
  #Connect to database
  $dbMode = 'write';
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'} =~ /local/i){
    $CURRENT_CONTEXT{'LOCAL_FL'}=1;
    $dbMode = 'local';
  }
  
  if(!($CURRENT_CONTEXT{'DBI_HANDLE'} = &_database_connect( $dbMode ))){
    &_log(505,'handler','Database connection');
    return &_end(APACHE_SERVER_ERROR);
  }

  if(!($CURRENT_CONTEXT{'DBI_SL_HANDLE'} = &_database_connect( 'read' ))){
    &_log(505,'handler','Database Slave connection');
    return &_end(APACHE_SERVER_ERROR);
  }
  
  #Retrieve server options
  if(&_get_sys_options()){
    &_log(503,'handler', 'System options');
    return &_end(APACHE_SERVER_ERROR);
  }
  
  # First, we determine the http method
  # The get method will be only available for the bootstrap to manage the deploy, and maybe, sometime to give files wich will be stored in the database
  if($r->method() eq 'GET'){

    # To manage the first contact with the bootstrap
    # The uri must be '/ocsinventory/deploy/[filename]'
    if($r->uri()=~/deploy\/([^\/]+)\/?$/){
      if($ENV{'OCS_OPT_DEPLOY'}){
        return &_end(&_send_file('deploy',$1));
      }else{
        return &_end(APACHE_FORBIDDEN);
      }
    }elsif($r->uri()=~/update\/(.+)\/(.+)\/(\d+)\/?/){
    # We use the GET method for the update to use the proxies
    # The URL is built like that : [OCSFSERVER]/ocsinventory/[os]/[name]/[version]
      if($ENV{'OCS_OPT_UPDATE'}){
        return &_end(&_send_file('update',$1,$2,$3));
      }else{
        return &_end(APACHE_FORBIDDEN);
      }
    }else{
    # If the url is invalid
      return &_end(APACHE_BAD_REQUEST);
    }

  # Here is the post method management
  }elsif($r->method eq 'POST'){
    
    # Get the data
    if( !read(STDIN, $data, $ENV{'CONTENT_LENGTH'}) ){
      &_log(512,'handler','Reading request') if $ENV{'OCS_OPT_LOGLEVEL'};
      return &_end(APACHE_SERVER_ERROR);
    }
    # Copying buffer because inflate() modify it
    $raw_data = $data;
    $CURRENT_CONTEXT{'RAW_DATA'} = \$raw_data;
    # Debug level for Apache::DBI (apache/error.log)
    # $Apache::DBI::DEBUG=2;
  
    # Read the request
    # Possibilities :
    # prolog : The agent wants to know if he have to send an inventory(and with wich options)
    # update : The agent wants to know if there is a newer version available
    # inventory : It is an inventory
    # system : Request to know the server's time response (and if it's alive) not yet implemented
    # file : Download files when upgrading (For the moment, only when upgrading)
    ##################################################
    #
    # Inflate the data
    unless($d = Compress::Zlib::inflateInit()){
      &_log(506,'handler','Compress stage') if $ENV{'OCS_OPT_LOGLEVEL'};
      return &_end(APACHE_BAD_REQUEST);
    }
    ($inflated, $status) = $d->inflate($data);
    unless( $status == Z_OK or $status == Z_STREAM_END){
      if( $ENV{OCS_OPT_COMPRESS_TRY_OTHERS} ){
        &_inflate(\$raw_data, \$inflated);
      }
      else{
        undef $inflated;
      }
      if(!$inflated){
        &_log(506,'handler','Compress stage');
        return &_end(APACHE_SERVER_ERROR);
      }
    }
    # Unicode support - The XML may not use UTF8
    if($ENV{'OCS_OPT_UNICODE_SUPPORT'}) {
     if($inflated =~ /^.+encoding="([\w+\-]+)/) {
          my $enc = $1;
          $inflated =~ s/$enc/UTF-8/;
          Encode::from_to($inflated, "$enc", "utf8");
      }
    }

    $CURRENT_CONTEXT{'DATA'} = \$inflated;
    ##########################
    # Parse the XML request
    # Retrieving xml parsing options if needed
    &_get_xml_parser_opt( \%XML_PARSER_OPT ) unless %XML_PARSER_OPT;
    unless($query = XML::Simple::XMLin( $inflated, %XML_PARSER_OPT )){
      &_log(507,'handler','Xml stage');
      return &_end(APACHE_BAD_REQUEST);
    }
    # Convert MAC addresses to uppercase.
    if ($query->{'CONTENT'}->{'NETWORKS'}) {
        my $i = 0;
        while ($query->{'CONTENT'}->{'NETWORKS'}[$i]) {
            $query->{'CONTENT'}->{'NETWORKS'}[$i]->{'MACADDR'} = uc($query->{'CONTENT'}->{'NETWORKS'}[$i]->{'MACADDR'});
            $i++;
        }
    }
    my $i;
    # Fix bad date strings and size values for software entries
    if ($query->{'CONTENT'}->{'SOFTWARES'}) {
        $i = 0;
        while ($query->{'CONTENT'}->{'SOFTWARES'}[$i]) {
            # Valid date strings must be YYYY/MM/DD
            if ($query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'INSTALLDATE'} =~ /^(\d\d\d\d)\/(\d\d)\/(\d\d)$/) {
                # Since input can be a random string, additionally check if it realy constitutes a valid date.
                if (check_date($1, $2, $3)) {
                    # Convert to ISO format for maximum portability.
                    $query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'INSTALLDATE'} = "$1-$2-$3";
                } else {
                    # Syntax correct, but values out of range
                    $query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'INSTALLDATE'} = undef;
                }
            } else {
                # Bad syntax.
                $query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'INSTALLDATE'} = undef;
            }

            if ($query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'FILESIZE'} !~ /^\d+$/) {
                # Bad syntax.
                $query->{'CONTENT'}->{'SOFTWARES'}[$i]->{'FILESIZE'} = undef;
            }
            $i++;
        }
    }
    # Fix bad date strings for 'DRIVES' entries (LP bug #887534)
    if ($query->{'CONTENT'}->{'DRIVES'}) {
        $i = 0;
        while ($query->{'CONTENT'}->{'DRIVES'}[$i]) {
            # Valid timestamp strings must be 'YYYY/[M]M/[D]D'. Time part will be truncated.
            if ($query->{'CONTENT'}->{'DRIVES'}[$i]->{'CREATEDATE'} =~ /^(\d\d\d\d)\/(\d{1,2})\/(\d{1,2})( |$)/) {
                # Since input can be a random string, additionally check if it realy constitutes a valid date.
                if (check_date($1, $2, $3)) {
                    # Convert to ISO format for maximum portability.
                    $query->{'CONTENT'}->{'DRIVES'}[$i]->{'CREATEDATE'} = "$1-$2-$3";
                } else {
                    # Syntax correct, but values out of range
                    $query->{'CONTENT'}->{'DRIVES'}[$i]->{'CREATEDATE'} = undef;
                }
            } else {
                # Bad syntax.
                $query->{'CONTENT'}->{'DRIVES'}[$i]->{'CREATEDATE'} = undef;
            }
            $i++;
        }
    }
    # Fix bad size for 'STORAGES' entries (LP bug #1436702)
    if ($query->{'CONTENT'}->{'STORAGES'}) {
        $i = 0;
        while ($query->{'CONTENT'}->{'STORAGES'}[$i]) {
            # Affected agents report size in varying units (GB, TB...). Since
            # the dimension is unknown, we cannot convert it to MB - we can't
            # even tell if incoming data is affected by the bug.
            # However, if the value contains a decimal point/comma, the database
            # will not accept it (an integer is expected). In that case, it gets
            # unset to proceed.
            if ($query->{'CONTENT'}->{'STORAGES'}[$i]->{'DISKSIZE'} !~ /^\d+$/) {
                $query->{'CONTENT'}->{'STORAGES'}[$i]->{'DISKSIZE'} = undef;
            }
            $i++;
        }
    }
    
    $CURRENT_CONTEXT{'XML_ENTRY'} = $query;

    # Get the request type
    my $request=$query->{QUERY};
    $CURRENT_CONTEXT{'DEVICEID'} = $query->{DEVICEID} or $CURRENT_CONTEXT{'DEVICEID'} = $query->{CONTENT}->{DEVICEID};
    
    unless($request eq 'UPDATE'){
      if(&_check_deviceid($Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'})){
        &_log(502,'inventory','Bad deviceid') if $ENV{'OCS_OPT_LOGLEVEL'};
        return &_end(APACHE_BAD_REQUEST);
      }
    }
    
     # Must be filled
    unless($request){
      &_log(500,'handler','Request not defined');
      return &_end(APACHE_BAD_REQUEST);
    }

    # Init global structure
    my $err = &_init();
    return &_end($err) if $err;
    
    # The three above are hardcoded
    if($request eq 'PROLOG'){
      my $ret = &_prolog();
      return(&_end($ret));
    }elsif($request eq 'INVENTORY'){
      my $ret = &_inventory_handler();
      return(&_end($ret))
    }elsif($request eq 'SYSTEM'){
      my $ret = &_system_handler();
      return(&_end($ret));
    }else{
      # Other request are handled by options
      my $handler = &_modules_get_request_handler($request);
      if($handler == 0){
        &_log(500,'handler', 'No handler');
        return APACHE_BAD_REQUEST;
      }else{
        my $ret = &{$handler}(\%CURRENT_CONTEXT);
        return(&_end($ret));
      }

    }

  }else{ return APACHE_FORBIDDEN }

}

sub _init{
  my $request;
  
  # Retrieve Device if exists
  $request = $CURRENT_CONTEXT{'DBI_HANDLE'}->prepare('
    SELECT DEVICEID,ID,' . compose_unix_timestamp('LASTCOME') . ' AS LCOME,' . compose_unix_timestamp('LASTDATE') . ' AS LDATE,QUALITY,FIDELITY 
    FROM hardware WHERE DEVICEID=?'
  );
  unless($request->execute($CURRENT_CONTEXT{'DEVICEID'})){
    return(APACHE_SERVER_ERROR);
  }
  
  if($CURRENT_CONTEXT{'DEVICEID'} and !$request->rows){
    # Workaround for bug #530881 in the Windows agent:
    # If DeviceID has been renamed and the hostname part is unchanged (i.e. only the timestamp part is different),
    # silently alter the deviceID in the database.
    if($CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{OLD_DEVICEID}
       and substr($CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{OLD_DEVICEID},0,-20)
        eq substr($CURRENT_CONTEXT{'DEVICEID'},0,-20)){
      $CURRENT_CONTEXT{'DBI_HANDLE'}->do('UPDATE HARDWARE SET DEVICEID=? WHERE DEVICEID=?',
					 {},
					 $CURRENT_CONTEXT{'DEVICEID'},
					 $CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{OLD_DEVICEID}
					 );
      # Retrieve Device if exists
      $request = $CURRENT_CONTEXT{'DBI_HANDLE'}->prepare('
        SELECT DEVICEID,ID,' . compose_unix_timestamp('LASTCOME') . ' AS LCOME,' . compose_unix_timestamp('LASTDATE') . ' AS LDATE,QUALITY,FIDELITY 
        FROM hardware WHERE DEVICEID=?'
      );
      unless($request->execute($CURRENT_CONTEXT{'DEVICEID'})){
        return(APACHE_SERVER_ERROR);
      }
      if($request->rows){
        # Forget that OLD_DEVICEID ever existed
        delete $CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{OLD_DEVICEID};
      }
    }
  }

  for my $ipreg (@TRUSTED_IP){
      if($CURRENT_CONTEXT{'IPADDRESS'}=~/^$ipreg$/){
        &_log(310,'handler','trusted_computer') if $ENV{'OCS_OPT_LOGLEVEL'};
        $CURRENT_CONTEXT{'IS_TRUSTED'} = 1;
      }
  }
          
  if($request->rows){
    my $row = $request->fetchrow_hashref;
    
    $CURRENT_CONTEXT{'EXIST_FL'} = 1;
    $CURRENT_CONTEXT{'DATABASE_ID'} = $row->{'ID'};
    $CURRENT_CONTEXT{'DETAILS'} = {
      'LCOME' => $row->{'LCOME'},
      'LDATE' => $row->{'LDATE'},
      'QUALITY' => $row->{'QUALITY'},
      'FIDELITY' => $row->{'FIDELITY'},
    };
    
    # Computing groups list 
    if($ENV{'OCS_OPT_ENABLE_GROUPS'}){
      $CURRENT_CONTEXT{'MEMBER_OF'} = [ &_get_groups() ];
    }
    else{
      $CURRENT_CONTEXT{'MEMBER_OF'} = [];
    }
    
    $CURRENT_CONTEXT{'PARAMS'} = { &_get_spec_params() };
    $CURRENT_CONTEXT{'PARAMS_G'} = { &_get_spec_params_g() };
  }else{
    $CURRENT_CONTEXT{'EXIST_FL'} = 0;
    $CURRENT_CONTEXT{'MEMBER_OF'} = [];
  }
  
  $request->finish;  
  return;
}
1;













