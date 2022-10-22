<?php

/**
 * Translation loader that parses gettext .po files
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

namespace Library\I18n\Translator\Loader;

use Gettext\Loader\StrictPoLoader;
use Gettext\Translation;
use Laminas\I18n\Translator\Loader\FileLoaderInterface;
use Laminas\I18n\Translator\TextDomain;

/**
 * Translation loader that parses gettext .po files
 *
 * This loader allows using gettext without a need for compiling .mo files.
 * Fuzzy translations will be treated as untranslated.
 */
class Po implements FileLoaderInterface
{
    public function load($locale, $filename)
    {
        $textDomain = new TextDomain();
        $loader = new StrictPoLoader();
        $translations = $loader->loadFile($filename);
        /** @var Translation */
        foreach ($translations as $translation) {
            $translated = $translation->getTranslation();
            if ($translated && !$translation->getFlags()->has('fuzzy')) {
                $textDomain[$translation->getOriginal()] = $translated;
            }
        }

        return $textDomain;
    }
}
