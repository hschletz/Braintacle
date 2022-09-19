<?php

/**
 * Information about a client's windows installation
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

namespace Model\Client;

/**
 * Information about a client's windows installation
 *
 * @property string $Workgroup Workgroup/domain
 * @property string $UserDomain Domain of logged in user (for local accounts this is identical to the client name)
 * @property string $Company Company name (set during installation)
 * @property string $Owner Owner (set during installation)
 * @property string $ProductKey Product Key
 * @property string $ProductId Product ID (installation-specific, may or may not be unique)
 * @property string $ManualProductKey Manually overridden product key (entered in Braintacle console)
 * @property string $CpuArchitecture CPU architecture (may be different from physical CPU's capabilities)
 */
class WindowsInstallation extends \Model\AbstractModel
{
}
