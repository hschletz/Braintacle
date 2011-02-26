#!/bin/bash

# Create and upload a new release from the working copy.
#
# $Id$
# 
# Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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

# USAGE: release.sh <new version number> <Savannah username>

# Stop upon errors
set -e

# Sanity check version number
VERSION=$1
if [[ ! $VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
    echo ERROR: Invalid version number!
    exit 1
fi

# Evaluate Savannah username
SAVANNAH_USER=$2
if [ -z $SAVANNAH_USER ]; then
    echo ERROR: No Savannah username specified!
    exit 1
fi

# Get the absolute path of the "development" directory
# (the directory where this script resides).
# It is used as a working directory for temporary files.
DEVDIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")

# Get the base directory of the whole tree (parent of $DEVDIR)
BASEDIR=$(readlink -f "$DEVDIR/..")

# The location of the temporary export tree
EXPORT="$DEVDIR/braintacle"

# Archive filenames
TARFILE="$DEVDIR/Braintacle-$VERSION.tar.gz"
ZIPFILE="$DEVDIR/Braintacle-$VERSION.zip"

# Base URL of Subversion repository.
# The last path component (typically /trunk) is stripped.
REPOSITORY=$(dirname $(svn info "$BASEDIR" | grep -E ^URL: | sed 's/^URL: //'))

# Remove existing export directory if it exists
if [ -d "$EXPORT" ]; then
    rm -r "$EXPORT"
fi

# Export the tree
svn export "$BASEDIR" "$EXPORT"

# The .mo files are not part of the repository.
# They must be generated for the release.
php "$EXPORT/tools/update-translation.php" --noextract

# Set permissions to a sensible default.
chmod -R g-w "$EXPORT"

# Delete existing archive files.
if [ -f "$TARFILE" ]; then
    rm "$TARFILE"
fi
if [ -f "$ZIPFILE" ]; then
    rm "$ZIPFILE"
fi

# Create the archives. The pathnames should be relative, so it is necessary to
# change to the working directory first.
cd "$DEVDIR"
tar -c braintacle | gzip -9 >"$TARFILE"
zip -r -9 -q "$ZIPFILE" braintacle

# Delete temporary export tree
rm -r "$EXPORT"

# Delete old versions from download area
echo 'rm *' | sftp hschletz@dl.sv.nongnu.org:/releases/braintacle

# Upload the archives
scp "$TARFILE" $SAVANNAH_USER@dl.sv.nongnu.org:/releases/braintacle
scp "$ZIPFILE" $SAVANNAH_USER@dl.sv.nongnu.org:/releases/braintacle

# Delete local archives
rm "$TARFILE"
rm "$ZIPFILE"

# Tag the release in the repository.
svn copy $REPOSITORY/trunk $REPOSITORY/tags/release-$VERSION -m "Tagged release $VERSION"
