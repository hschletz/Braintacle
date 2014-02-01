<?php
/**
 * Translation adapter that parses gettext .po files directly without a need for
 * compiling .mo files
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Library
 */
/**
 * Translation adapter that parses gettext .po files directly without a need for
 * compiling .mo files. Fuzzy translations will be treated as untranslated.
 * @package Library
 */
class Braintacle_Translate_Adapter_Po extends Zend_Translate_Adapter
{

    /**
     * List of escape sequences that need unescaping in message strings.
     * @var array
     **/
    protected static $_escapeSequences = array(
        '\\\\' => '\\',
        '\\"' => '"',
        '\\n' => "\n",
    );

    /**
     * @ignore
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        $file = new SplFileObject($data);
        $file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);

        $translations = array();
        $state = 0; // Parser state; 0 := everything else; 1 := msgid; 2 := msgstr
        $msgid = ''; // current msgid
        $msgstr = ''; // current msgstr
        $fuzzy = false; // TRUE if current message is marked as fuzzy

        foreach ($file as $line) {
            if ($state == 0 or $state == 2) {
                if (preg_match('/^msgid\\s(.*)/', $line, $matches)) {
                    // Begin new message. Add last message to result list except
                    // for empty msgid and untranslated messages.
                    if ($msgid != '' and $msgstr != '') {
                        $translations[$locale][$msgid] = $msgstr;
                    }
                    $line = $matches[1];
                    $state = 1;
                    $msgid = '';
                    $msgstr = '';
                } elseif (preg_match('/^#,.*fuzzy/', $line)) {
                    $fuzzy = true;
                }
            }
            if ($state == 0) {
                continue;
            }
            if ($state == 1) {
                if (preg_match('/^msgstr\\s(.*)/', $line, $matches)) {
                    // msgid complete, begin reading msgstr
                    $line = $matches[1];
                    $state = 2;
                    if ($fuzzy) {
                        // Message is marked as fuzzy, Ignore it.
                        $msgid = '';
                        $fuzzy = false;
                    }
                }
            }
            if (preg_match('/^"(.*)"$/', $line, $matches)) {
                $line = strtr($matches[1], self::$_escapeSequences);
                // Append string to msgid or msgstr, depending on parser state.
                // This supports strings that spans multiple lines.
                if ($state == 1) {
                    $msgid .= $line;
                } else {
                    $msgstr .= $line;
                }
            } else {
                $state = 0;
            }
        }
        // The last entry is not added inside the loop.
        if ($msgid != '' and $msgstr != '') {
            $translations[$locale][$msgid] = $msgstr;
        }
        return $translations;
    }

    /**
     * @ignore
     */
    public function toString()
    {
        return 'Po';
    }

}
