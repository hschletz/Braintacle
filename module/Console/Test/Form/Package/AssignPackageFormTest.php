<?php

namespace Console\Test\Inputfilter;

use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\TemplateTestTrait;
use Console\Form\Package\AssignPackagesForm;
use Console\Template\Functions\TranslateFunction;
use Laminas\Session\Validator\Csrf;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\ClientOrGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AssignPackageForm
 */
class AssignPackageFormTest extends TestCase
{
    use DomMatcherTrait;
    use MockeryPHPUnitIntegration;
    use TemplateTestTrait;

    private function createTarget(array $assignablePackages)
    {
        /** @var MockObject|ClientOrGroup */
        $target = $this->createMock(ClientOrGroup::class);
        $target->method('getAssignablePackages')->willReturn($assignablePackages);

        return $target;
    }

    private function renderToString(array $values): string
    {
        $csrfTokenFunction = $this->createStub(CsrfTokenFunction::class);
        $csrfTokenFunction->method('__invoke')->willReturnCallback(fn () => 'token');

        $translateFunction = $this->createStub(TranslateFunction::class);
        $translateFunction->method('__invoke')->willReturnCallback(fn ($message) => '_' . $message . '_');

        $templateEngine = $this->createTemplateEngine([
            CsrfTokenFunction::class => $csrfTokenFunction,
            TranslateFunction::class => $translateFunction
        ]);
        $content = $templateEngine->render('Forms/AssignPackage.latte', $values);

        return $content;
    }

    public function testProcessValid()
    {
        $formData = [
            'csrf' => (new Csrf())->getHash(),
            'packages' => ['package1', 'package2'],
        ];

        $target = Mockery::mock(ClientOrGroup::class);
        $target->shouldReceive('getAssignablePackages')->andReturn(['package1', 'package2', 'package3']);
        $target->shouldReceive('assignPackage')->once()->with('package1');
        $target->shouldReceive('assignPackage')->once()->with('package2');

        $form = new AssignPackagesForm();
        $form->process($formData, $target);
    }

    public function testProcessInvalidCsrf()
    {
        $formData = [
            'csrf' => 'invalid',
            'packages' => ['package'],
        ];

        $target = $this->createTarget(['package']);
        $target->expects($this->never())->method('assignPackage');

        $form = new AssignPackagesForm();
        $messages = $form->process($formData, $target);

        $this->assertCount(1, $messages);
        $this->assertTrue(isset($messages['csrf'][Csrf::NOT_SAME]));
    }

    public function testProcessInvalidNoPackages()
    {
        $formData = [
            'csrf' => (new Csrf())->getHash(),
        ];

        $target = $this->createTarget(['package']);
        $target->expects($this->never())->method('assignPackage');

        $form = new AssignPackagesForm();
        $messages = $form->process($formData, $target);

        $this->assertCount(1, $messages);
        $this->assertTrue(isset($messages['packages'][NotEmpty::IS_EMPTY]));
    }

    public function testProcessInvalidEmptyPackages()
    {
        $formData = [
            'csrf' => (new Csrf())->getHash(),
            'packages' => [],
        ];

        $target = $this->createTarget(['package']);
        $target->expects($this->never())->method('assignPackage');

        $form = new AssignPackagesForm();
        $messages = $form->process($formData, $target);

        $this->assertCount(1, $messages);
        $this->assertTrue(isset($messages['packages'][NotEmpty::IS_EMPTY]));
    }

    public function testProcessInvalidIncorrectPackage()
    {
        $formData = [
            'csrf' => (new Csrf())->getHash(),
            'packages' => ['package'],
        ];

        $target = $this->createTarget([]);
        $target->expects($this->never())->method('assignPackage');

        $form = new AssignPackagesForm();
        $messages = $form->process($formData, $target);

        $this->assertCount(1, $messages);
        $this->assertTrue(isset($messages['packages'][InArray::NOT_IN_ARRAY]));
    }

    public function testTemplateWithoutPackages()
    {
        $content = $this->renderToString(['packages' => []]);
        $this->assertEquals('', $content);
    }

    public function testTemplateWithPackages()
    {
        $xPath = $this->createXpath($this->renderToString([
            'csrfToken' => 'token',
            'packages' => ['package1', 'package2']
        ]));
        $this->assertXpathMatches($xPath, '//h2[text()="_Assign packages_"]');
        $this->assertXpathMatches($xPath, '//form/input[@name="csrf"][@value="token"]');
        $this->assertXpathMatches($xPath, '//form/div[@class="table"]');
        $this->assertXpathMatches($xPath, '//label/input[@type="checkbox"][@name="packages[]"][@value="package1"]');
        $this->assertXpathMatches($xPath, '//label/span[text()="package1"]');
        $this->assertXpathMatches($xPath, '//label/input[@type="checkbox"][@name="packages[]"][@value="package2"]');
        $this->assertXpathMatches($xPath, '//label/span[text()="package2"]');
    }
}
