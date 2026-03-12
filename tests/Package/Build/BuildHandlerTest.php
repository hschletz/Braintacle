<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\BuildHandler;
use Braintacle\Package\Build\ValidationErrors;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use Exception;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Package\RuntimeException as PackageRuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;

#[CoversClass(BuildHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
#[UsesClass(ValidationErrors::class)]
final class BuildHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use MockeryPHPUnitIntegration;
    use TemplateTestTrait;

    private function createHandler(
        ?DataProcessor $dataProcessor = null,
        ?TranslatorInterface $translator = null,
        ?Builder $builder = null,
        ?FlashMessenger $flashMessenger = null,
        ?RouteHelper $routeHelper = null,
        ?TemplateEngine $templateEngine = null,
    ): BuildHandler {
        return new BuildHandler(
            $this->response,
            $dataProcessor ?? $this->createStub(DataProcessor::class),
            $translator ?? $this->createStub(TranslatorInterface::class),
            $builder ?? $this->createStub(Builder::class),
            $flashMessenger ?? $this->createStub(FlashMessenger::class),
            $routeHelper ?? $this->createStub(RouteHelper::class),
            $templateEngine ?? $this->createStub(TemplateEngine::class),
        );
    }

    private function getResponse(
        BuildHandler $handler,
        array $queryParams = [],
        array $parsedBody = [],
        ?array $uploadedFiles = null,
    ): ResponseInterface {
        if ($uploadedFiles === null) {
            $uploadedFiles = ['file' => $this->createStub(UploadedFileInterface::class)];
        }

        return $handler->handle($this->request
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody)
            ->withUploadedFiles($uploadedFiles));
    }

    public static function invalidFileProvider()
    {
        return [
            [[]], // Missing upload data
            [['file' => [ // More than 1 file
                Mockery::mock(UploadedFileInterface::class),
                Mockery::mock(UploadedFileInterface::class),
            ]]],
        ];
    }

    #[DataProvider('invalidFileProvider')]
    public function testInvalidFile(array $uploadedFiles)
    {
        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Bad file');

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('build');
        $builder->expects($this->never())->method('update');

        $handler = $this->createHandler(builder: $builder);
        $this->getResponse($handler, uploadedFiles: $uploadedFiles);
    }

    #[TestWith([[], Package::class])]
    #[TestWith([['updateFrom' => 'oldPackage'], PackageUpdate::class])]
    public function testDataProcessorArgumentsAndException(array $queryParams, string $class)
    {
        $parsedBody = ['foo' => 'bar'];
        $exception = new Exception('test');

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($parsedBody, $class)->willThrowException($exception);

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('build');
        $builder->expects($this->never())->method('update');

        $handler = $this->createHandler(dataProcessor: $dataProcessor, builder: $builder);
        try {
            $this->getResponse($handler, $queryParams, $parsedBody);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertSame($exception, $e);
        }
    }

    #[TestWith([[], false])]
    #[TestWith([['updateFrom' => 'oldPackage'], true])]
    public function testValidationErrorDisplaysInputValues(array $queryParams, bool $isUpdate)
    {
        $parsedBody = [
            'name' => '_name',
            'comment' => '_comment',
            'platform' => 'linux',
            'action' => 'execute',
            'actionParam' => '_actionParam',
            'priority' => '5',
            'maxFragmentSize' => '42',
            'warn' => 'on',
            'warnCountdown' => '60',
            'warnMessage' => '_warnMessage',
            'warnAllowAbort' => 'on',
            'warnAllowDelay' => 'on',
            'postInstMessage' => '_postInstMessage',
        ];
        if ($isUpdate) {
            $parsedBody += [
                'deployPending' => 'on',
                'deployRunning' => 'on',
                'deploySuccess' => 'on',
                'deployError' => 'on',
                'deployGroups' => 'on',
            ];
        }

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willThrowException($this->createStub(ValidationErrors::class));

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('build');
        $builder->expects($this->never())->method('update');

        $templateEngine = $this->createTemplateEngine();

        $handler = $this->createHandler(
            dataProcessor: $dataProcessor,
            builder: $builder,
            templateEngine: $templateEngine,
        );
        $response = $this->getResponse(
            $handler,
            $queryParams,
            $parsedBody,
        );

        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertXpathMatches($xPath, '//input[@name="name"][@value="_name"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="comment"][text()="_comment"]');
        $this->assertXpathMatches($xPath, '//select[@name="platform"]/option[@value="linux"][@selected]');
        $this->assertXpathMatches($xPath, '//select[@name="action"]/option[@value="execute"][@selected]');
        $this->assertXpathMatches($xPath, '//input[@name="actionParam"][@value="_actionParam"]');
        $this->assertXpathMatches($xPath, '//input[@name="priority"][@value="5"]');
        $this->assertXpathMatches($xPath, '//input[@name="maxFragmentSize"][@value="42"]');
        $this->assertXpathMatches($xPath, '//input[@name="warn"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@name="warnCountdown"][@value="60"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="warnMessage"][text()="_warnMessage"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="postInstMessage"][text()="_postInstMessage"]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowAbort"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowDelay"][@checked]');
        if ($isUpdate) {
            $this->assertXpathCount(5, $xPath, '//input[starts-with(@name, "deploy")]');
            $this->assertXpathMatches($xPath, '//input[@name="deployPending"][@checked]');
            $this->assertXpathMatches($xPath, '//input[@name="deployRunning"][@checked]');
            $this->assertXpathMatches($xPath, '//input[@name="deploySuccess"][@checked]');
            $this->assertXpathMatches($xPath, '//input[@name="deployError"][@checked]');
            $this->assertXpathMatches($xPath, '//input[@name="deployGroups"][@checked]');
        } else {
            $this->assertXpathCount(0, $xPath, '//input[starts-with(@name, "deploy")]');
        }
    }

    #[TestWith(['name', 'nameExistsMessage'])]
    #[TestWith(['warnMessage', 'warnMessageInvalidMessage'])]
    #[TestWith(['postInstMessage', 'postInstMessageInvalidMessage'])]
    public function testValidationErrorMessages(string $elementName, string $messageIdentifier)
    {
        // Minimal set of required template variables
        $parsedBody = [
            'platform' => 'linux',
            'action' => 'execute',
            'actionParam' => '',
            'priority' => '',
            'maxFragmentSize' => '',
            'warnMessage' => '',
            'warnCountdown' => '',
            'postInstMessage' => '',
        ];

        $messages = [
            'nameExistsMessage' => null,
            'warnMessageInvalidMessage' => null,
            'postInstMessageInvalidMessage' => null,
        ];
        $messages[$messageIdentifier] = 'validation message';
        $exception = new ValidationErrors(...$messages);

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willThrowException($exception);

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('build');
        $builder->expects($this->never())->method('update');

        $templateEngine = $this->createTemplateEngine();

        $handler = $this->createHandler(
            dataProcessor: $dataProcessor,
            builder: $builder,
            templateEngine: $templateEngine,
        );
        $response = $this->getResponse($handler, parsedBody: $parsedBody);

        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertXpathCount(1, $xPath, '//*[@class="error"]');
        $this->assertXpathMatches(
            $xPath,
            "//*[@name='$elementName']/following-sibling::*[@class='error'][text()='validation message']",
        );
    }

    #[TestWith([[], false, false, "_Package 'package_name' was successfully created."])]
    #[TestWith([[], false, true, 'error message'])]
    #[TestWith([
        ['updateFrom' => 'oldPackage'],
        true,
        false,
        "_Package 'oldPackage' was successfully changed to 'package_name'.",
    ])]
    #[TestWith([
        ['updateFrom' => 'oldPackage'],
        true,
        true,
        "_Error changing Package 'oldPackage' to 'package_name': error message",
    ])]
    public function testBuilder(array $queryParams, bool $isUpdate, bool $throw, string $message)
    {
        $package = $this->createStub($isUpdate ? PackageUpdate::class : Package::class);
        $package->name = 'package_name';

        $file = $this->createStub(UploadedFileInterface::class);

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturn($package);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn($message) => '_' . $message);

        $builder = $this->createMock(Builder::class);
        if ($isUpdate) {
            $builder->expects($this->never())->method('build');
            $invocationMocker = $builder->expects($this->once())->method('update')->with($package, $file, 'oldPackage');
        } else {
            $invocationMocker = $builder->expects($this->once())->method('build')->with($package, $file);
            $builder->expects($this->never())->method('update');
        }
        if ($throw) {
            $invocationMocker->willThrowException(new PackageRuntimeException('error message'));
        }

        $flashMessenger = $this->createMock(FlashMessenger::class);
        if ($throw) {
            $flashMessenger->expects($this->never())->method('addSuccessMessage');
            $flashMessenger->expects($this->once())->method('adderrorMessage')->with($message);
        } else {
            $flashMessenger->expects($this->once())->method('addSuccessMessage')->with($message);
            $flashMessenger->expects($this->never())->method('addErrorMessage');
        }

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('packagesList')->willReturn('redirectTo');

        $handler = $this->createHandler(
            dataProcessor: $dataProcessor,
            translator: $translator,
            builder: $builder,
            flashMessenger: $flashMessenger,
            routeHelper: $routeHelper,
        );
        $response = $this->getResponse($handler, $queryParams);

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['redirectTo']], $response);
    }
}
