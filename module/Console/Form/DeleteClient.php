<?php

/**
 * Confirmation for deleting clients
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
 * Confirmation for deleting clients
 *
 * Only one of "yes" or "no" will show up in POST data. The "DeleteInterfaces"
 * checkbox state should be passed to \Model\Client\ClientManager::deleteClient().
 *
 * The init() method requires the "config" option to be set to a \Model\Config
 * instance. The factory sets this automatically.
 */
class DeleteClient extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $deleteInterfaces = new \Laminas\Form\Element\Checkbox('DeleteInterfaces');
        $deleteInterfaces->setLabel('Delete interfaces from network listing')
                         ->setChecked($this->getOption('config')->defaultDeleteInterfaces);
        $this->add($deleteInterfaces);

        $yes = new \Library\Form\Element\Submit('yes');
        $yes->setLabel('Yes');
        $this->add($yes);

        $no = new \Library\Form\Element\Submit('no');
        $no->setLabel('No');
        $this->add($no);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Laminas\View\Renderer\PhpRenderer $view, \Laminas\Form\Fieldset $fieldset)
    {
        $output = $view->htmlElement(
            'div',
            $view->formRow($fieldset->get('DeleteInterfaces'), 'append')
        );
        $output .= $view->htmlElement(
            'div',
            $view->formRow($fieldset->get('yes')) . $view->formRow($fieldset->get('no'))
        );
        return $output;
    }
}
