<?php

/**
 * Controller for managing duplicate clients
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

namespace Console\Controller;

/**
 * Controller for managing duplicate clients and the criteria for determining duplicates.
 */
class DuplicatesController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Duplicates prototype
     * @var \Model\Client\DuplicatesManager
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
     * @param \Model\Client\DuplicatesManager $duplicates
     * @param \Console\Form\ShowDuplicates $showDuplicates
     */
    public function __construct(
        \Model\Client\DuplicatesManager $duplicates,
        \Console\Form\ShowDuplicates $showDuplicates
    ) {
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
     * Form for displaying and merging duplicate clients
     *
     * @return array|\Laminas\Http\Response array(form) or Redirect response
     */
    public function manageAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->_showDuplicates->setData($this->params()->fromPost());
            if ($this->_showDuplicates->isValid()) {
                $data = $this->_showDuplicates->getData();
                $this->_duplicates->merge($data['clients'], $data['mergeOptions']);
                $this->flashMessenger()->addSuccessMessage($this->_('The selected clients have been merged.'));
                return $this->redirectToRoute('duplicates', 'index');
            }
        }
        $this->setActiveMenu('Inventory', 'Duplicates');
        $ordering = $this->getOrder('Id', 'asc');
        $clients = $this->_duplicates->find(
            $this->params()->fromQuery('criteria'),
            $ordering['order'],
            $ordering['direction']
        );
        $this->_showDuplicates->setOptions(
            array(
                'clients' => $clients,
                'order' => $ordering['order'],
                'direction' => $ordering['direction'],
            )
        );
        return array('form' => $this->_showDuplicates);
    }

    /**
     * Allow given criteria and value as duplicate
     *
     * @return array|\Laminas\Http\Response criteria/value for GET, redirect response for POST
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
                    sprintf($this->_("'%s' is no longer considered duplicate."), $value)
                );
                return $this->redirectToRoute('duplicates', 'index');
            } else {
                // Operation cancelled by user
                return $this->redirectToRoute('duplicates', 'manage', array('criteria' => $criteria));
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
