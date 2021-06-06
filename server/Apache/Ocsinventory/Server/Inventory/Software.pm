###############################################################################
## Copyright 2005-2020 OCSInventory-NG/OCSInventory-Server contributors.
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
package Apache::Ocsinventory::Server::Inventory::Software;

use Apache::Ocsinventory::Map;

use strict;
use warnings;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _get_info_software
  _del_all_soft
  _insert_software
  _prepare_sql
  _trim_value
/;

sub _prepare_sql {
    my ($sql, @arguments) = @_;
    my $query;
    my $i = 1;

    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

    $query = $dbh->prepare($sql);
    foreach my $value (@arguments) {
        $query->bind_param($i, $value);
        $i++;
    }
    $query->execute or return undef; 

    return $query;   
}

sub _get_info_software {
    my ($value, $table, $column) = @_;
    my $sql;
    my $valueResult = undef;
    my $result;
    my $resultVerif;

    # Verif if value exist
    my @argVerif = ();
    $sql = "SELECT ID FROM $table WHERE $column = ?";
    push @argVerif, $value;
    $resultVerif = _prepare_sql($sql, @argVerif);
    if(!defined $resultVerif) { return undef; }

    while(my $row = $resultVerif->fetchrow_hashref()){
        $valueResult = $row->{ID};
    }

    if(!defined $valueResult) {
        my @argInsert = ();

        # Insert if undef
        $sql = "INSERT INTO $table ($column) VALUES(?)";
        push @argInsert, $value;

        $result = _prepare_sql($sql, @argInsert);
        if(!defined $result) { return undef; }

        # Get last Insert or Update ID
        my @argSelect = ();
        $sql = "SELECT ID FROM $table WHERE $column = ?";
        push @argSelect, $value;
        $result = _prepare_sql($sql, @argSelect);
        if(!defined $result) { return undef; }

        while(my $row = $result->fetchrow_hashref()){
            $valueResult = $row->{ID};
        }
    }

    return $valueResult;
}

sub _del_all_soft {
    my ($hardware_id) = @_;
    my $sql;
    my @arg = ();
    my $result;

    $sql = "DELETE FROM software WHERE HARDWARE_ID = ?";
    push @arg, $hardware_id;
    $result = _prepare_sql($sql, @arg);
    if(!defined $result) { return 1; }

    return 0;
}

sub _trim_value {
    my ($toTrim) = @_;

    $toTrim =~ s/^\s+|\s+$//g;

    return $toTrim;
}

sub _insert_software {
    my $sql;
    my $hardware_id = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
    my @arrayRef = ('HARDWARE_ID', 'DEFINITION_ID', 
                    'PUBLISHER', 'VERSION', 
                    'FOLDER', 'COMMENTS', 'FILENAME', 
                    'FILESIZE', 'SOURCE', 'GUID', 
                    'LANGUAGE', 'INSTALLDATE', 'BITSWIDTH');

    if(_del_all_soft($hardware_id)) { return 1; }

    foreach my $software (@{$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{SOFTWARES}}) {
        my %arrayValue = (
            "HARDWARE_ID"   => $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'},
            "DEFINITION_ID" => undef,
            "PUBLISHER"     => $software->{PUBLISHER},
            "VERSION"       => $software->{VERSION}, 
            "FOLDER"        => $software->{FOLDER},
            "COMMENTS"      => $software->{COMMENTS},
            "FILENAME"      => $software->{FILENAME},
            "FILESIZE"      => $software->{FILESIZE},
            "SOURCE"        => $software->{SOURCE},
            "GUID"          => $software->{GUID},
            "LANGUAGE"      => $software->{LANGUAGE},
            "INSTALLDATE"   => $software->{INSTALLDATE},
            "BITSWIDTH"     => $software->{BITSWIDTH}
        );
        my $name = $software->{NAME} // ''; # NULL is not allowed.
        my @bind_num;
        
        # Get software Name ID
        $arrayValue{DEFINITION_ID} = _get_info_software($name, "software_definitions", "name");
        if(!defined $arrayValue{DEFINITION_ID}) { return 1; }

        my $arrayRefString = join ',', @arrayRef;
        my @arg = ();
        foreach my $arrayKey(@arrayRef) {
            push @bind_num, '?';
            push @arg, $arrayValue{$arrayKey};
        }

        $sql = "INSERT INTO software ($arrayRefString) VALUES(";
        $sql .= (join ',', @bind_num).') ';
        my $result = _prepare_sql($sql, @arg);
        if(!defined $result) { return 1; }
    }

    return 0;
}

1;