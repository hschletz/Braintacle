#!/bin/bash

# Update MAC address database.
#
# Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
#
# This program is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by the Free
# Software Foundation; either version 2 of the License, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
# FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
# more details.
#
# You should have received a copy of the GNU General Public License along with
# this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


# Get the absolute path of the "development" directory
# (the directory where this script resides).
DEVDIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")

# Get the base directory of the whole tree (parent of $DEVDIR)
BASEDIR=$(readlink -f "$DEVDIR/..")

curl -Lo $BASEDIR/module/Library/data/MacAddress/manuf \
     'https://gitlab.com/wireshark/wireshark/-/raw/master/manuf?inline=false'
