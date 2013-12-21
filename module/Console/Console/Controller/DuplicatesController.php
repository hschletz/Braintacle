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
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Constructor
     *
     * @param \Model_Computer
     */
    public function __construct(\Model\Config $config, \Model_Computer $computer)
    {
        $this->_config = $config;
        $this->_computer = $computer;
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
            $num = $this->_computer->findDuplicates($criteria, true);
            if ($num) {
                $duplicates[$criteria] = $num;
            }
        }
        return array(
            'messages' => $this->flashMessenger()->getSuccessMessages(), // from merge/allow action
            'duplicates' => $duplicates,
        );
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
        $params['criteria'] = $this->params()->fromQuery('criteria');
        $params['computers'] = $this->_computer->findDuplicates(
            $params['criteria'],
            false,
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
        $this->_computer->mergeComputers(
            $params->fromPost('computers'),
            $params->fromPost('mergeUserdefined'),
            $params->fromPost('mergeGroups'),
            $params->fromPost('mergePackages')
        );
        $this->flashMessenger()->addSuccessMessage('The selected computers have been merged.');
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
                $this->_computer->allowDuplicates($criteria, $value);
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
