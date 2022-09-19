<?php

/**
 * Translate and format messages with sprintf()-style placeholders
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

/**
 * Translate and format messages with sprintf()-style placeholders
 */
class FormatMessages extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * EscapeHtml view helper
     * @var \Laminas\View\Helper\EscapeHtml
     */
    protected $_escapeHtml;

    /**
     * HtmlElement view helper
     * @var \Library\View\Helper\HtmlElement
     */
    protected $_htmlElement;

    /**
     * DateFormat view helper
     * @var \Laminas\I18n\View\Helper\Translate
     */
    protected $_translate;

    /**
     * Constructor
     *
     * @param \Laminas\View\Helper\EscapeHtml $escapeHtml
     * @param \Library\View\Helper\HtmlElement $htmlElement
     * @param \Laminas\I18n\View\Helper\Translate $translate
     */
    public function __construct(
        \Laminas\View\Helper\EscapeHtml $escapeHtml,
        \Library\View\Helper\HtmlElement $htmlElement,
        \Laminas\I18n\View\Helper\Translate $translate
    ) {
        $this->_escapeHtml = $escapeHtml;
        $this->_htmlElement = $htmlElement;
        $this->_translate = $translate;
    }

    /**
     * Translate and format messages with sprintf()-style placeholders
     *
     * This helper takes a list of items and returns a list of translated and
     * formatted messages. Each item can be a simple string which will just be
     * translated, or an associative array. Only the first element of an array
     * item is evaluated. The key is a message string with sprintf()-style
     * placeholders that will be translated and then be fed with the array
     * value, which must be an array of arguments. If only 1 argument is
     * required, it can be passed directly.
     *
     * Example:
     *
     *     $input = array(
     *         'message1',
     *         array('message2 %s' => 'arg'),
     *         array('message3 %s %s' => array('arg1', 'arg2'))
     *     );
     *     $output = array(
     *         'translated1',
     *         'translated2 arg',
     *         'translated3 arg1 arg2'
     *     );
     *
     * All strings and arguments get escaped. \Laminas\Uri\Http arguments are
     * converted to hyperlinks.
     *
     * @param mixed[] $messages
     * @return string[]
     */
    public function __invoke(array $messages)
    {
        foreach ($messages as &$message) {
            if (is_array($message)) {
                $format = key($message);
                $args = current($message);
                if (!is_array($args)) {
                    $args = array($args);
                }
                foreach ($args as &$arg) {
                    if ($arg instanceof \Laminas\Uri\Http) {
                        $arg = $this->_htmlElement->__invoke(
                            'a',
                            $this->_escapeHtml->__invoke($arg),
                            array('href' => $arg),
                            true
                        );
                    } else {
                        $arg = $this->_escapeHtml->__invoke($arg);
                    }
                }
                $message = vsprintf($this->_translate->__invoke($format), $args);
            } else {
                $message = $this->_translate->__invoke($message);
            }
        }
        return $messages;
    }
}
