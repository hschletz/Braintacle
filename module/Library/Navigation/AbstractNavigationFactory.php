<?php

/**
 * Abstract navigation factory
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

namespace Library\Navigation;

/**
 * Abstract navigation factory
 *
 * This abstract class extends
 * \Laminas\Navigation\Service\DefaultNavigationFactory with a more flexible
 * configuration source. Instead of pulling configuration from the "navigation"
 * section of the application config, the configuration is provided by the
 * _getConfig() method which must be implemented by derived classes.
 *
 * @codeCoverageIgnore
 */
abstract class AbstractNavigationFactory extends \Laminas\Navigation\Service\DefaultNavigationFactory
{
    /** {@inheritdoc} */
    protected function getPages(\Interop\Container\ContainerInterface $container)
    {
        if (!$this->pages) {
            $pages = $this->getPagesFromConfig($this->getConfig());
            $this->pages = $this->preparePages($container, $pages);
        }
        return $this->pages;
    }

    /**
     * Construct navigation config
     *
     * @return string|\Laminas\Config\Config|array Configuration file/object/array
     */
    abstract protected function getConfig();

    /**
     * Translation helper
     *
     * This is a dummy method that can be called by _getConfig() to mark label
     * strings translatable. It does not do anything (it returns the string
     * unchanged), but using it allows detection and extraction of translatable
     * strings.
     *
     * @param string $string Translatable string
     * @return string same as $string
     */
    // @codingStandardsIgnoreStart
    protected function _($string)
    {
        return $string;
    }
    // @codingStandardsIgnoreEnd
}
