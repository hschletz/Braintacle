<?php

/**
 * Get description for builtin client filter specification
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

namespace Console\View\Helper;

/**
 * Get description for builtin client filter specification
 */
class FilterDescription extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Subnet instance for address calculations
     * @var \Model\Network\Subnet
     */
    protected $_subnet;

    /**
     * Constructor
     *
     * @param \Model\Network\Subnet $subnet
     */
    public function __construct(\Model\Network\Subnet $subnet)
    {
        $this->_subnet = $subnet;
    }

    /**
     * Get description for builtin client filter specification
     *
     * The following filters are recognized:
     *
     * - PackagePending
     * - PackageRunning
     * - PackageSuccess
     * - PackageError
     * - Software
     * - Windows.ManualProductKey
     * - array('NetworkInterface.Subnet', 'NetworkInterface.Netmask')
     *
     * @param string|array $filter Name of a builtin filter routine
     * @param string|array $search Search parameter
     * @param integer $count Number of results
     * @return string Description, escaped
     * @throws \InvalidArgumentException if no description is available for the filter
     */
    public function __invoke($filter, $search, $count)
    {
        // Multiple filters?
        if (is_array($filter)) {
            if ($filter === array('NetworkInterface.Subnet', 'NetworkInterface.Netmask')) {
                $description = $this->view->translate(
                    '%1$d clients with an interface in network %2$s'
                );
                $subnet = clone $this->_subnet;
                $subnet['Address'] = $search[0];
                $subnet['Mask'] = $search[1];
                return $this->view->escapeHtml(sprintf($description, $count, $subnet['CidrAddress']));
            }
            // No other multi-filters defined.
            throw new \InvalidArgumentException(
                'No description available for this set of multiple filters'
            );
        }

        // Single filter
        switch ($filter) {
            case 'PackagePending':
                $description = $this->view->translate(
                    '%1$d clients waiting for notification of package \'%2$s\''
                );
                break;
            case 'PackageRunning':
                $description = $this->view->translate(
                    '%1$d clients with deployment of package \'%2$s\' in progress'
                );
                break;
            case 'PackageSuccess':
                $description = $this->view->translate(
                    '%1$d clients with package \'%2$s\' successfully deployed'
                );
                break;
            case 'PackageError':
                $description = $this->view->translate(
                    '%1$d clients where deployment of package \'%2$s\' failed'
                );
                break;
            case 'Software':
                $description = $this->view->translate(
                    '%1$d clients where software \'%2$s\' is installed'
                );
                break;
            case 'Windows.ManualProductKey':
                $description = $this->view->translate(
                    '%1$d clients with manually entered product key'
                );
                break;
            default:
                throw new \InvalidArgumentException('No description available for filter ' . $filter);
        }
        return $this->view->escapeHtml(sprintf($description, $count, $search));
    }
}
