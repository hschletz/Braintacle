###############################################################################
## Copyright 2005-2016 OCSInventory-NG/OCSInventory-Server contributors.
## See the Contributors file for more details about them.
##
## This file is part of OCSInventory-NG/OCSInventory-ocsreports.
##
## OCSInventory-NG/OCSInventory-Server is free software: you can redistribute
## it and/or modify it under the terms of the GNU General Public License as
## published by the Free Software Foundation, either version 2 of the License,
## or (at your option) any later version.
##
## OCSInventory-NG/OCSInventory-Server is distributed in the hope that it
## will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
## of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
## Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
## MA 02110-1301, USA.
################################################################################
package Apache::Ocsinventory::Server::Modperl2;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw/
  APACHE_SERVER_ERROR
  APACHE_FORBIDDEN
  APACHE_OK
  APACHE_BAD_REQUEST
  APACHE_METHOD_NOT_ALLOWED
  _set_http_header
  _set_http_content_type
  _get_http_header
  _send_http_headers
/;

# Load modules only if running under mod_perl. They may not exist otherwise and
# are not needed.
if (exists $ENV{MOD_PERL}) {
    require Apache2::Connection; 
    require Apache2::SubRequest; 
    require Apache2::Access; 
    require Apache2::RequestIO; 
    require Apache2::RequestUtil;
    require Apache2::RequestRec; 
    require Apache2::ServerUtil; 
    require Apache2::Log;
    'Apache2::log'->import;
    require APR::Table; 
}

# Cannot import constants from Apache2::Const because that module may not exist
# when not running under mod_perl. Define constants manually.
use constant APACHE_OK => 0;
use constant APACHE_BAD_REQUEST => 400;
use constant APACHE_FORBIDDEN => 403;
use constant APACHE_METHOD_NOT_ALLOWED => 405;
use constant APACHE_SERVER_ERROR => 500;

# Wrappers
sub _set_http_header{
  my ($header, $value, $r) = @_;
  $r->headers_out->{$header} = $value;
  
}

sub _set_http_content_type{
  my ($type, $r) = @_;
  $r->content_type($type);
}

sub _get_http_header{
  my ($header, $r) = @_;
  return $r->headers_in->{$header};
}

sub _send_http_headers{
  return;
}
1;
