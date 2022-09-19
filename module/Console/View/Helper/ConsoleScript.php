<?php

/**
 * Helper for setting and retrieving script elements for HTML head section
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

namespace Console\View\Helper;

use Laminas\Uri\Uri;
use Laminas\Uri\UriInterface;
use Library\Application;
use LogicException;

/**
 * Helper for setting and retrieving script elements for HTML head section
 *
 * This is similar to the HeadScript helper. If invoked with a script name, the
 * given script from /public/js will be appended to the head section. Scripts
 * are loaded as modules.
 */
class ConsoleScript extends \Laminas\View\Helper\Placeholder\Container\AbstractStandalone
{
    public function __invoke(string $script = null): self
    {
        if ($script) {
            $this->getContainer()->append($script);
        }

        return $this;
    }

    public function toString()
    {
        $scripts = [];
        foreach ($this as $script) {
            $scripts[] = $this->getHtml($script);
        }

        return implode($this->getSeparator(), $scripts);
    }

    /**
     * Generate HTML to load given script.
     */
    public function getHtml(string $script): string
    {
        $uri = $this->getUri($script);
        $src = $this->getView()->escapeHtmlAttr($uri);

        return sprintf('<script src="%s" type="module"></script>', $src);
    }

    /**
     * Get Uri for given script.
     *
     * URI will contain the script file's mtime as query parameter (cachebuster).
     */
    public function getUri(string $script): UriInterface
    {
        $filename = $this->getFile($script);
        $path = '/js/' . $script;
        $uri = new Uri($this->getView()->basePath($path));
        $uri->setQuery((string) filemtime($filename));

        return $uri;
    }

    /**
     * Get absolute file path for given script.
     *
     * @throws LogicException if file does not exist below public/js/
     */
    public function getFile(string $script): string
    {
        return Application::getPath('public/js/' . $script);
    }
}
