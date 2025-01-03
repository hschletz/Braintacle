<?php

/**
 * Controller for managing duplicate clients
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Direction;
use Console\Template\TemplateViewModel;
use Laminas\Mvc\MvcEvent;
use Model\Config;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Controller for managing duplicate clients and the criteria for determining duplicates.
 */
class DuplicatesController extends \Laminas\Mvc\Controller\AbstractActionController
{
    private Config $config;

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
        Config $config,
        \Model\Client\DuplicatesManager $duplicates,
        \Console\Form\ShowDuplicates $showDuplicates
    ) {
        $this->config = $config;
        $this->_duplicates = $duplicates;
        $this->_showDuplicates = $showDuplicates;
    }

    public function onDispatch(MvcEvent $e)
    {
        $event = $this->getEvent();
        $event->setParam('template', 'InventoryMenuLayout.latte');
        $event->setParam('subMenuRoute', 'duplicatesList');

        return parent::onDispatch($e);
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

        $criteria = $this->params()->fromQuery('criteria');
        $ordering = $this->getOrder('Id', 'asc');
        $clients = $this->_duplicates->find(
            $criteria,
            $ordering['order'],
            $ordering['direction']
        );

        $messages = $this->_showDuplicates->getMessages();
        $context = [
            // Flatten $messages to a single-level list
            'messages' => new RecursiveIteratorIterator(new RecursiveArrayIterator($messages)),
            'clients' => $clients,
            'criteria' => $criteria,
            'csrfToken' => $this->_showDuplicates->get('_csrf')->getValue(),
            'config' => $this->config,
            'order' => $ordering['order'],
            'direction' => Direction::from($ordering['direction']),
        ];

        return new TemplateViewModel('Forms/ShowDuplicates.latte', $context);
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
