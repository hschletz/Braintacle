#!/bin/bash

# Update a working copy of the Braintacle SVN tree
#
# This is intended for installations that run directly off an SVN working copy.
# The script pulls the latest changes from the repository, compiles updated
# translations and updates the database if necessary.
#
# WARNING: any local changes to the languages directory will be lost!
#
#
# Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

svn update $BASEDIR

# Update .mo files which are not versioned
php $BASEDIR/tools/update-translation.php --noextract

# Revert any changes to versioned files made by previous command
svn revert -R $BASEDIR/languages

# Update database schema
php $BASEDIR/tools/schema-manager.php
