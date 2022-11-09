<?php

namespace Console\Test\Inputfilter;

use Console\Form\Package\AssignPackagesForm;
use Console\Template\TemplateRenderer;
use Console\Validator\CsrfValidator;
use Laminas\Dom\Document;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Latte\Engine;
use Library\Test\DomMatcherTrait;
use Model\ClientOrGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AssignPackageForm
 */
class AssignPackageFormTest extends TestCase
{
    use DomMatcherTrait;

    private function createTarget(array $assignablePackages)
    {
        /** @var MockObject|ClientOrGroup */
        $target = $this->createMock(ClientOrGroup::class);
        $target->method('getAssignablePackages')->willReturn($assignablePackages);

        return $target;
    }

    private function renderToString(array $values): string
    {
        $engine = new Engine();
        $engine->addFunction('translate', fn($message) => '_' . $message . '_');
        $renderer = new TemplateRenderer($engine);
        $content = $renderer->render('Forms/AssignPackage.latte', $values);

        return $content;
    }

    private function renderToDocument(array $values): Document
    {
        $content = $this->renderToString($values);
        $document = $this->createDocument($content);

        return $document;
    }

    public function testProcessValid()
    {
        $formData = [
            'csrf' => CsrfValidator::getToken(),
            'packages' => ['package1', 'package2'],
        ];

        $target = $this->createTarget(['package1', 'package2', 'package3']);
        $target->expects($this->exactly(2))->method('assignPackage')->withConsecutive(['package1'], ['package2']);

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
        $this->assertTrue(isset($messages['csrf'][CsrfValidator::NOT_SAME]));
    }

    public function testProcessInvalidNoPackages()
    {
        $formData = [
            'csrf' => CsrfValidator::getToken(),
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
            'csrf' => CsrfValidator::getToken(),
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
            'csrf' => CsrfValidator::getToken(),
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
        $document = $this->renderToDocument([
            'action' => 'action',
            'csrfToken' => 'token',
            'packages' => ['package1', 'package2']
        ]);
        $this->assertXpathMatches($document, '//h2[text()="_Assign packages_"]');
        $this->assertXpathMatches($document, '//form[@action="action"]');
        $this->assertXpathMatches($document, '//form/input[@name="csrf"][@value="token"]');
        $this->assertXpathMatches($document, '//form/div[@class="table"]');
        $this->assertXpathMatches($document, '//label/input[@type="checkbox"][@name="packages[]"][@value="package1"]');
        $this->assertXpathMatches($document, '//label/span[text()="package1"]');
        $this->assertXpathMatches($document, '//label/input[@type="checkbox"][@name="packages[]"][@value="package2"]');
        $this->assertXpathMatches($document, '//label/span[text()="package2"]');
    }
}
