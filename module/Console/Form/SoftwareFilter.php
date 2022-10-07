<?php

/**
 * Select filter for software overview
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

namespace Console\Form;

/**
 * Select filter for software overview
 *
 * This form has its method set to "GET" by default. It has a single select
 * element "filter" with the values "accepted", "ignored", "new" and "all". The
 * value can be preset via setFilter().
 */
class SoftwareFilter extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $this->setAttribute('method', 'GET');

        $filter = new \Laminas\Form\Element\Select('filter');
        $filter->setLabel('Filter')
               ->setValueOptions(
                   array(
                       'accepted' => $this->_('selected for display'),
                       'ignored' => $this->_('ignored for display'),
                       'new' => $this->_('new or not categorized'),
                       'all' => $this->_('all'),
                   )
               )
               ->setAttribute('onchange', 'this.form.submit();');
        $this->add($filter);
    }

    /**
     * Set the value for the 'filter' element
     *
     * @param string $filter One of accepted|ignored|new|all
     * @throws \InvalidArgumentException if $filter is invalid
     */
    public function setFilter($filter)
    {
        $element = $this->get('filter');
        if (!array_key_exists($filter, $element->getValueOptions())) {
            throw new \InvalidArgumentException('Invalid filter value: ' . $filter);
        }
        $element->setValue($filter);
    }
}
