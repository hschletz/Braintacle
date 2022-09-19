<?php

/**
 * Tests for the main layout template
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

use Console\View\Helper\ConsoleScript;
use Laminas\Dom\Document\Query;

/**
 * Tests for the main layout template
 */
class LayoutTest extends \PHPUnit\Framework\TestCase
{
    protected $_view;
    protected $_authService;

    public function setUp(): void
    {
        $this->_authService = $this->createMock('Model\Operator\AuthenticationService');

        $application = \Library\Application::init('Console');
        $serviceManager = $application->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $serviceManager->setService(
            'Laminas\Authentication\AuthenticationService',
            $this->_authService
        );

        $this->_view = new \Laminas\View\Renderer\PhpRenderer();
        $this->_view->setHelperPluginManager($serviceManager->get('ViewHelperManager'));
        $this->_view->setResolver(
            new \Laminas\View\Resolver\TemplateMapResolver(
                array('layout' => \Console\Module::getPath('views/layout/layout.php'))
            )
        );
    }

    public function testMinimalLayout()
    {
        $html = $this->_view->render('layout');
        $document = new \Laminas\Dom\Document($html);
        $dom = $document->getDomDocument();

        $doctype = $dom->doctype;
        $this->assertEquals('html', $doctype->name);
        $this->assertEquals('', $doctype->publicId);
        $this->assertEquals('', $doctype->systemId);

        $this->assertCount(1, Query::execute('/html', $document));
        $this->assertCount(
            1,
            Query::execute(
                '/html/head/meta[@http-equiv="Content-Type"][@content="text/html; charset=UTF-8"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                sprintf(
                    '/html/head/link[@href="/style.css?%d"][@media="screen"][@rel="stylesheet"][@type="text/css"]',
                    filemtime(__DIR__ . '/../../../public/style.css')
                ),
                $document
            )
        );
    }

    public function testTitle()
    {
        $this->_view->headTitle()->setTranslatorEnabled(false)->append('title');
        $html = $this->_view->render('layout');
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/head/title[text()="title"]', $document));
    }

    public function testConsoleScript()
    {
        $helper = $this->createMock(ConsoleScript::class);
        $helper->method('__invoke')->with(null)->willReturnSelf();
        $helper->method('__toString')->willReturn('<consolescript/>');
        $this->_view->getHelperPluginManager()->setService('consoleScript', $helper);

        $html = $this->_view->render('layout');
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/head/consolescript', $document));
    }

    public function testContent()
    {
        $html = $this->_view->render('layout', array('content' => 'content'));
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(1, Query::execute("/html/body/div[@id='content'][text()='\ncontent\n']", $document));
    }

    public function testNoIdentity()
    {
        $this->_authService = $this->createMock('Model\Operator\AuthenticationService');
        $this->_authService->method('hasIdentity')->willReturn(false);

        $html = $this->_view->render('layout');
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(0, Query::execute('//div[@id="menu"]', $document));
    }

    public function testIdentityButNoRoute()
    {
        $this->_authService->method('hasIdentity')->willReturn(true);
        $this->_authService->method('getIdentity')->willReturn('identity');

        $html = $this->_view->render('layout', array('noRoute' => true));
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(0, Query::execute('//div[@id="menu"]', $document));
    }

    public function testMenu()
    {
        $this->_authService->method('hasIdentity')->willReturn(true);
        $this->_authService->method('getIdentity')->willReturn('identity');

        $this->_view->plugin('navigation')->menu()->setTranslator(null);

        $menu = \Laminas\Navigation\Page\AbstractPage::factory(
            array(
                'type' => 'uri',
                'pages' => array(
                    array(
                        'label' => 'main',
                        'uri' => 'mainUri',
                        'active' => true,
                        'pages' => array(
                            array(
                                'label' => 'sub',
                                'uri' => 'subUri',
                                'active' => true,
                            ),
                        ),
                    ),
                ),
            )
        );
        $html = $this->_view->render('layout', array('menu' => $menu));
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(
            1,
            Query::execute(
                '/html/body/div[@id="menu"]/ul[@class="navigation"]/li/a[@href="mainUri"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '/html/body/div[@id="menu"]/ul[@class="navigation navigation_sub"]/li/a[@href="subUri"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                "/html/body/div[@id='menu']/div[@id='logout']/a[@href='/console/login/logout/'][text()='\nAbmelden\n']",
                $document
            )
        );
    }
}
