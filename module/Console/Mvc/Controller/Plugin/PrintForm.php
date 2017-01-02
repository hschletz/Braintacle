<?php
/**
 * Return ViewModel which outputs a given form
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

/**
 * Return ViewModel which outputs a given form
 *
 * Many actions simply render a form object. Instead of writing a bunch of
 * identical scripts with a single rendering method call, this plugin provides a
 * generic template which simply renders the provided form.
 */
class PrintForm extends \Zend\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Return view model set up to output given form
     *
     * @param mixed $form Form to render
     * @return \Zend\View\Model\ViewModel
     */
    public function __invoke($form)
    {
        $view = new \Zend\View\Model\ViewModel;
        $view->setTemplate('plugin/PrintForm.php');
        $view->form = $form;
        return $view;
    }
}
