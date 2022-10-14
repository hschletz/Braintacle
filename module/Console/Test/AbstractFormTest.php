<?php

/**
 * Abstract form test case
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

namespace Console\Test;

use Laminas\Validator\Translator\TranslatorInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract form test case
 *
 * This base class performs common setup and tests for all forms derived from
 * \Console\Form\Form.
 */
abstract class AbstractFormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * HTML header to declare document encoding
     *
     * \DomDocument parses HTML input as ISO 8859-1 by default. This is a
     * problem when XPath queries test on non-ASCII-characters. The only way to
     * specify another encoding is a meta tag within the HTML code itself.
     * For HTML fragments, this header can be prepended to trick \DomDocument
     * (and \Laminas\Dom\Document) to parse the fragment as UTF-8.
     */
    const HTML_HEADER = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';

    /**
     * Backup of default translator
     * @var \Laminas\Validator\Translator\TranslatorInterface
     */
    protected $_defaultTranslatorBackup;

    /**
     * Form instance provided by setUp()
     * @var \Console\Form\Form
     */
    protected $_form;

    public function setUp(): void
    {
        /** @var MockObject|TranslatorInterface */
        $translator = $this->createMock('\Laminas\Validator\Translator\TranslatorInterface');
        $translator->method('translate')->willReturnCallback(array($this, 'translatorMock'));
        $this->_defaultTranslatorBackup = \Laminas\Validator\AbstractValidator::getDefaultTranslator();
        \Laminas\Validator\AbstractValidator::setDefaultTranslator($translator);

        $this->_form = $this->getForm();
    }

    public function tearDown(): void
    {
        \Laminas\Validator\AbstractValidator::setDefaultTranslator($this->_defaultTranslatorBackup);
    }

    /**
     * Replacement for translator
     */
    public function translatorMock($message)
    {
        return "TRANSLATE($message)";
    }

    /**
     * Hook to provide form instance
     *
     * The default implementation instantiates an object of a class derived from
     * the test class name. Override this method to use another name or
     * construct the form instance manually. The overridden method is
     * responsible for calling init() on the form.
     */
    protected function getForm()
    {
        $class = $this->getFormClass();
        $form = new $class();
        $form->init();
        return $form;
    }

    /**
     * Get the name of the form class, derived from the test class name
     *
     * @return string Form class name
     */
    protected function getFormClass()
    {
        // Derive form class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Create a view renderer
     *
     * A new view renderer instance is created on every call. If the state of
     * the renderer or a helper needs to be preserved, call this only once and
     * store it in a variable.
     *
     * @return \Laminas\View\Renderer\PhpRenderer
     */
    protected function createView()
    {
        $serviceManager = \Library\Application::init('Console')->getServiceManager();
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array(
                    'report missing translations' => true,
                ),
            )
        );
        $view = new \Laminas\View\Renderer\PhpRenderer();
        $view->setHelperPluginManager(clone $serviceManager->get('ViewHelperManager'));
        return $view;
    }

    /**
     * Callback for mocking formElementErrors helper
     *
     * Can be used to replace the formElementErrors helper with a simplified
     * function which bypasses some of the original helper's functionality,
     * in particular the translator.
     *
     * If the element contains messages, the first one will be rendered in an UL
     * element with class "errorMock".
     *
     * $attributes is ignored here. The parameter should be checked by the mock
     * object.
     *
     * @param \Laminas\Form\ElementInterface $element
     * @param array $attributes
     * @return string
     */
    public function formElementErrorsMock($element, $attributes)
    {
        $messages = $element->getMessages();
        if ($messages) {
            return sprintf('<ul class="errorMock"><li>%s</li></ul>', $messages[0]);
        } else {
            return '';
        }
    }

    /**
     * Test basic form properties (form class, "class" attribute, CSRF element)
     */
    public function testForm()
    {
        $this->assertInstanceOf('Console\Form\Form', $this->_form);

        $classes = 'form ' . substr(strtr(strtolower($this->getFormClass()), '\\', '_'), 8);
        $pattern = '/(.+ )?' . preg_quote($classes, '/') . '( .+)?/';
        $this->assertMatchesRegularExpression($pattern, $this->_form->getAttribute('class'));

        $this->assertInstanceOf('\Laminas\Form\Element\Csrf', $this->_form->get('_csrf'));
    }
}
