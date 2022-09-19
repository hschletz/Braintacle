<?php

/**
 * Package build form renderer
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

namespace Console\View\Helper\Form\Package;

use Console\Form\Package\Build as FormBuild;
use Console\View\Helper\Form\FormHelperInterface;
use Laminas\Form\FormInterface;
use Laminas\View\Helper\AbstractHelper;

/**
 * Package build form renderer
 */
class Build extends AbstractHelper implements FormHelperInterface
{
    public function __invoke(FormInterface $form = null)
    {
        $view = $this->getView();
        $view->consoleScript('form_package.js');

        $this->initLabels($form);

        return $view->consoleForm($form);
    }

    /**
     * Initialize label for ActionParam element.
     *
     * The label is set dynamically depending on the DeployAction value. Attach
     * translated labels to be picked up by JS code.
     */
    public function initLabels(FormBuild $form): void
    {
        // The label must be initialized with its untranslated text for its
        // placeholder to get rendered.
        $view = $this->getView();
        $commandLine = $view->translate('Command line');
        $labels = [
            'launch' => 'Command line',
            'execute' => 'Command line',
            'store' => 'Target path',
        ];
        $labelsTranslated = [
            'launch' => $commandLine,
            'execute' => $commandLine,
            'store' => $view->translate('Target path'),
        ];
        $actionParam = $form->get('ActionParam');
        $actionParam->setAttribute('data-labels', json_encode($labelsTranslated));
        $actionParam->setLabel($labels[$form->get('DeployAction')->getValue()]);
    }
}
