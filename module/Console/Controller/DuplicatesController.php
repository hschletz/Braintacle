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

use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\MergeDuplicatesHandler;
use Braintacle\Http\RouteHelper;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container as Session;

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

    public function __construct(
        private RouteHelper $routeHelper,
        private Session $session,
        \Model\Client\DuplicatesManager $duplicates,
    ) {
        $this->_duplicates = $duplicates;
    }

    public function onDispatch(MvcEvent $e)
    {
        $event = $this->getEvent();
        $event->setParam('template', 'MainMenu/InventoryMenuLayout.latte');
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
        foreach (Criterion::cases() as $criterion) {
            $num = $this->_duplicates->count($criterion->name);
            if ($num) {
                $duplicates[$criterion->value] = $num;
            }
        }

        return [
            'routeHelper' => $this->routeHelper,
            'duplicates' => $duplicates,
            'merged' => isset($this->session[MergeDuplicatesHandler::class]),
        ];
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
            }
            return $this->redirectToRoute('duplicates', 'index');
        } else {
            // View script renders form
            return array(
                'criteria' => $criteria,
                'value' => $value,
            );
        }
    }
}
