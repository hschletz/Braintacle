<?php

/**
 * Package
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Model\Package;

/**
 * Package
 *
 * All properties should be considered readonly. Instances and data are provided
 * by PackageManager. Manipulation by any other means is not supported and can
 * give unpredictable results.
 *
 * @property integer $Id Numeric package ID, historically a UNIX timestamp
 * @property string $Name Name to uniquely identify package
 * @property \DateTime $Timestamp Timestamp of package creation
 * @property integer $Priority Download priority (0-10)
 * @property integer $NumFragments Number of download fragments
 * @property integer $Size Download size
 * @property string $Platform One of 'windows', 'linux' or 'mac'
 * @property string $Comment Comment
 * @property integer $NumPending Number of clients with pending packages,
 * provided by PackageManager::getPackages()
 * @property integer $NumRunning Number of clients currently downloading/installing package,
 * provided by PackageManager::getPackages()
 * @property integer $NumSuccess Number of clients with successful deployment,
 * provided by PackageManager::getPackages()
 * @property integer $NumError Number of clients with unsuccessful deployment,
 * provided by PackageManager::getPackages()
 * @property string $HashType Hash type (recommended: SHA256 for Windows packages, SHA1 for others)
 * @property string $Hash Hash of assembled package,
 * @property string $DeployAction One of 'store', 'execute', 'launch',
 * provided by PackageManager::getPackage()
 * @property string $ActionParam Path for storage or command to execute, depending on action,
 * provided by PackageManager::getPackage()
 * @property bool $Warn Whether the user should be notified before deployment,
 * provided by PackageManager::getPackage()
 * @property string $WarnMessage Message to display before deployment,
 * provided by PackageManager::getPackage()
 * @property integer $WarnCountdown Timeout in seconds before deployment starts,
 * provided by PackageManager::getPackage()
 * @property bool $WarnAllowAbort Whether the user should be allowed to abort,
 * provided by PackageManager::getPackage()
 * @property bool $WarnAllowDelay Whether the user should be allowed to delay,
 * provided by PackageManager::getPackage()
 * @property string $PostInstMessage Message to display after deployment,
 * provided by PackageManager::getPackage()
 */
class Package extends \Model\AbstractModel
{
    public function exchangeArray($input): array
    {
        if (isset($input['Id'])) {
            // Add Timestamp property
            $input['Timestamp'] = new \DateTime("@$input[Id]");
        }
        return parent::exchangeArray($input);
    }
}
