<?php
/**
 * Controller for managing duplicate computers
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
     * Duplicates prototype
     * @var \Model\Computer\Duplicates
     */
    protected $_duplicates;

    /**
     * ShowDuplicates prototype
     * @var \Console\Form\ShowDuplicates
     */
    protected $_showDuplicates;

    /**
     * Constructor
     *
     * @param \Model\Computer\Duplicates $duplicates
     * @param \Console\Form\ShowDuplicates $showDuplicates
     */
    public function __construct(
        \Model\Computer\Duplicates $duplicates,
        \Console\Form\ShowDuplicates $showDuplicates
    )
    {
        $this->_duplicates = $duplicates;
        $this->_showDuplicates = $showDuplicates;
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
        $search = $this->getOrder('Id', 'asc');
        $computers = $this->_duplicates->find(
            $this->params()->fromQuery('criteria'),
            $search['order'],
            $search['direction']
        );
        $this->_showDuplicates->setOptions(
            array(
                'computers' => $computers,
                'order' => $search['order'],
                'direction' => $search['direction'],
            )
        );
        return array('form' => $this->_showDuplicates);
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
        $this->_showDuplicates->setData($this->params()->fromPost());
        if ($this->_showDuplicates->isValid()) {
            $data = $this->_showDuplicates->getData();
            $this->_duplicates->merge(
                $data['computers'],
                $data['mergeCustomFields'],
                $data['mergeGroups'],
                $data['mergePackages']
            );
            $this->flashMessenger()->addSuccessMessage('The selected computers have been merged.');
        } else {
            $messages = $this->_showDuplicates->getMessages();
            array_walk_recursive(
                $messages,
                function($message) {
                    $this->flashMessenger()->addInfoMessage($message);
                }
            );
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
