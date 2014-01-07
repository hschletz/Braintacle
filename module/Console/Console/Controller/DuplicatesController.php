<?php
/**
 * Controller for managing duplicate computers
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Controller;

/**
 * Controller for managing duplicate computers and the criteria for determining duplicates.
 */
class DuplicatesController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Computer prototype
     * @var \Model\Computer\duplicates
     */
    protected $_duplicates;

    /**
     * Constructor
     *
     * @param \Model_Computer
     */
    public function __construct(\Model\Config $config, \Model\Computer\Duplicates $duplicates)
    {
        $this->_config = $config;
        $this->_duplicates = $duplicates;
    }

    /**
     * Display overview of duplicates
     *
     * @return array duplicates => array(Name|MacAddress|Serial|AssetTag => count (only if > 0))
     */
    public function indexAction()
    {
        $duplicates = array();
        foreach (array('Name', 'MacAddress', 'Serial', 'AssetTag') as $criteria) {
            $num = $this->_duplicates->count($criteria);
            if ($num) {
                $duplicates[$criteria] = $num;
            }
        }
        return array('duplicates' => $duplicates);
    }

    /**
     * Display overview of duplicates of given criteria
     *
     * @return array order, direction, criteria, computers
     */
    public function showAction()
    {
        $this->setActiveMenu('Inventory', 'Duplicates');
        $params = $this->getOrder('Id', 'asc');
        $params['computers'] = $this->_duplicates->find(
            $this->params()->fromQuery('criteria'),
            $params['order'],
            $params['direction']
        );
        $params['config'] = $this->_config;
        return $params;
    }

    /**
     * Merge selected computers
     *
     * @return \Zend\Http\Response Redirect response to index page
     * @throws \RuntimeException if not invoked via POST
     */
    public function mergeAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new \RuntimeException('Action "merge" can only be invoked via POST');
        }
        $params = $this->params();
        $computers = array_unique($params->fromPost('computers', array()));
        if (count($computers) >= 2) {
            $this->_duplicates->merge(
                $computers,
                $params->fromPost('mergeUserdefined'),
                $params->fromPost('mergeGroups'),
                $params->fromPost('mergePackages')
            );
            $this->flashMessenger()->addSuccessMessage('The selected computers have been merged.');
        } else {
            $this->flashMessenger()->addInfoMessage('At least 2 different computers have to be selected.');
        }
        return $this->redirectToRoute('duplicates', 'index');
    }

    /**
     * Allow given criteria and value as duplicate
     *
     * @return array|Zend\Http\Response criteria/value for GET, redirect response for POST
     */
    public function allowAction()
    {
        $params = $this->params();
        $criteria = $params->fromQuery('criteria');
        $value = $params->fromQuery('value');

        if ($this->getRequest()->isPost()) {
            if ($params->fromPost('yes')) {
                $this->_duplicates->allow($criteria, $value);
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        '"%s" is no longer considered duplicate.',
                        $value
                    )
                );
                return $this->redirectToRoute('duplicates', 'index');
            } else {
                // Operation cancelled by user
                return $this->redirectToRoute('duplicates', 'show', array('criteria' => $criteria));
            }
        } else {
            // View script renders form
            return array(
                'criteria' => $criteria,
                'value' => $value,
            );
        }
    }
}
