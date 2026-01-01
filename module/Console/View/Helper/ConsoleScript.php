<?php

/**
 * Helper for setting and retrieving script elements for HTML head section
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Template\Function\AssetUrlFunction;
use Laminas\Escaper\Escaper;

/**
 * Load script as module.
 */
class ConsoleScript
{
    public function __construct(
        private AssetUrlFunction $assetUrl,
        private Escaper $escaper,
    ) {}

    /**
     * Generate HTML to load given script.
     */
    public function __invoke(string $script): string
    {
        $uri = ($this->assetUrl)($script);

        return sprintf('<script src="%s" type="module"></script>', $this->escaper->escapeHtmlAttr($uri));
    }
}
