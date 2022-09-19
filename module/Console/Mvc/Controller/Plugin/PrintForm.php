<?php

/**
 * Return ViewModel which outputs a given form
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

namespace Console\Mvc\Controller\Plugin;

use Console\Form\Form;
use Laminas\View\Model\ViewModel;

/**
 * Return ViewModel which outputs a given form
 *
 * Many actions simply render a form object. Instead of writing a bunch of
 * identical scripts with a single rendering method call, this plugin provides a
 * generic template which renders the provided form by using the provided helper
 * or falling back to the form's render() method (deprecated).
 */
class PrintForm extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Return view model set up to output given form
     */
    public function __invoke(Form $form, string $helperName = null): ViewModel
    {
        $view = new ViewModel();
        $view->setTemplate('plugin/PrintForm.php');
        $view->form = $form;
        $view->helperName = $helperName;

        return $view;
    }
}
