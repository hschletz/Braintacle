<?php

namespace Console\Test\View\Helper;

use Console\Template\TemplateRenderer;
use Console\View\Helper\GroupHeader;
use Laminas\View\Helper\Navigation;
use Laminas\View\Helper\Navigation\Menu;
use Library\Test\DomMatcherTrait;
use Library\Test\View\Helper\AbstractTest;
use Model\Group\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for the GroupHeader helper
 */
class GroupHeaderTest extends AbstractTest
{
    use DomMatcherTrait;

    public function testInvoke()
    {
        /** @var MockObject|Menu */
        $menu = $this->createMock(Menu::class);
        $menu->expects($this->once())
             ->method('setUlClass')
             ->with('navigation navigation_details')
             ->willReturnSelf();
        $menu->expects($this->once())
             ->method('render')
             ->with()
             ->willReturn('<ul>menu</ul>');

        /** @var MockObject|Navigation */
        $navigation = $this->createMock(Navigation::class);
        $navigation->expects($this->once())
                   ->method('__invoke')
                   ->with('Console\Navigation\GroupMenu')
                   ->willReturnSelf();
        $navigation->expects($this->once())
                   ->method('__call')
                   ->with('menu', [])
                   ->willReturn($menu);

        $templateRenderer = static::$serviceManager->get(TemplateRenderer::class);
        $helper = new GroupHeader($navigation, $templateRenderer);

        $group = new Group();
        $group->name = 'a<b>';

        $content = $helper($group);
        $document = $this->createDocument($content);
        $this->assertXpathMatches($document, '//h1[text()="Einzelheiten für Gruppe \'a<b>\'"]');
        $this->assertXpathMatches($document, '//ul[text()="menu"]');
    }
}
