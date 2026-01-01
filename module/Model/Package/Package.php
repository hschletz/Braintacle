<?php

/**
 * Package
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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
 * @property integer $id Numeric package ID, historically a UNIX timestamp
 * @property string $name Name to uniquely identify package
 * @property \DateTime $timestamp Timestamp of package creation
 * @property integer $priority Download priority (0-10)
 * @property integer $numFragments Number of download fragments
 * @property integer $size Download size
 * @property string $platform One of 'windows', 'linux' or 'mac'
 * @property string $comment Comment
 * @property integer $numPending Number of clients with pending packages,
 * provided by PackageManager::getPackages()
 * @property integer $numRunning Number of clients currently downloading/installing package,
 * provided by PackageManager::getPackages()
 * @property integer $numSuccess Number of clients with successful deployment,
 * provided by PackageManager::getPackages()
 * @property integer $numError Number of clients with unsuccessful deployment,
 * provided by PackageManager::getPackages()
 * @property string $hashType Hash type (recommended: SHA256 for Windows packages, SHA1 for others)
 * @property string $hash Hash of assembled package,
 * @property string $deployAction One of 'store', 'execute', 'launch',
 * provided by PackageManager::getPackage()
 * @property string $actionParam Path for storage or command to execute, depending on action,
 * provided by PackageManager::getPackage()
 * @property bool $warn Whether the user should be notified before deployment,
 * provided by PackageManager::getPackage()
 * @property string $warnMessage Message to display before deployment,
 * provided by PackageManager::getPackage()
 * @property integer $warnCountdown Timeout in seconds before deployment starts,
 * provided by PackageManager::getPackage()
 * @property bool $warnAllowAbort Whether the user should be allowed to abort,
 * provided by PackageManager::getPackage()
 * @property bool $warnAllowDelay Whether the user should be allowed to delay,
 * provided by PackageManager::getPackage()
 * @property string $postInstMessage Message to display after deployment,
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
