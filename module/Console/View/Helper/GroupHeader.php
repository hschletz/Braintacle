<?php

/**
 * Render group headline and navigation
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

namespace Console\View\Helper;

use Braintacle\Template\TemplateEngine;
use Laminas\View\Helper\AbstractHelper;
use Model\Group\Group;

/**
 * Render group headline and navigation
 *
 * @codeCoverageIgnore
 */
class GroupHeader extends AbstractHelper
{
    public function __construct(private TemplateEngine $templateRenderer, private string $currentAction)
    {
    }

    public function __invoke(Group $group): string
    {
        return $this->templateRenderer->render(
            'Pages/Group/Header.latte',
            [
                'group' => $group,
                'currentAction' => $this->currentAction,
            ]
        );
    }
}
