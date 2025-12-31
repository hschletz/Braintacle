<?php

namespace Braintacle\Test\Client\Import;

use Braintacle\Client\Import\Importer;
use Braintacle\Client\Import\ImportError;
use Braintacle\Client\Import\ImportHandler;
use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\Session\Validator\Csrf as CsrfValidator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;

#[CoversClass(ImportHandler::class)]
final class ImportHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    private function createHandler(
        ?CsrfValidator $csrfValidator = null,
        ?Importer $importer = null,
        ?RouteHelper $routeHelper = null,
        ?FlashMessages $flashMessages = null,
    ): ImportHandler {
        if (!$csrfValidator) {
            $csrfValidator = $this->createMock(CsrfValidator::class);
            $csrfValidator->method('isValid')->willReturn(true);
            $csrfValidator->expects($this->never())->method('getMessages');
        }
        if (!$importer) {
            $importer = $this->createMock(Importer::class);
            $importer->expects($this->never())->method('importStream');
        }
        if (!$flashMessages) {
            $flashMessages = $this->createMock(FlashMessages::class);
            $flashMessages->expects($this->never())->method('add');
        }
        return new ImportHandler(
            $this->response,
            $csrfValidator,
            $importer,
            $routeHelper ?? $this->createStub(RouteHelper::class),
            $flashMessages,
        );
    }

    public static function invalidCsrfTokenProvider()
    {
        return [
            [[], ''],
            [['csrfToken' => 'invalid'], 'invalid'],
        ];
    }

    #[DataProvider('invalidCsrfTokenProvider')]
    public function testInvalidCsrfToken(array $postData, string $token)
    {
        $csrfValidator = $this->createMock(CsrfValidator::class);
        $csrfValidator->method('isValid')->with($token)->willReturn(false);
        $csrfValidator->method('getMessages')->willReturn([CsrfValidator::NOT_SAME => '_message']);

        $handler = $this->createHandler(csrfValidator: $csrfValidator);

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('_message');

        $handler->handle($this->request->withParsedBody($postData));
    }

    public static function invalidFilesProvider()
    {
        return [
            [[]],
            [['file' => [Mockery::mock(UploadedFileInterface::class), Mockery::mock(UploadedFileInterface::class)]]],
        ];
    }

    #[DataProvider('invalidFilesProvider')]
    public function testInvalidFiles(array $files)
    {
        $handler = $this->createHandler();

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Missing or bad file');

        $handler->handle($this->request->withUploadedFiles($files));
    }

    public function testRequestSuccess()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('clientList')->willReturn('_redirect');

        $stream = $this->createStub(StreamInterface::class);

        $importer = $this->createMock(Importer::class);
        $importer->expects($this->once())->method('importStream')->with($stream);

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getStream')->willReturn($stream);

        $handler = $this->createHandler(routeHelper: $routeHelper, importer: $importer);
        $response = $handler->handle($this->request->withUploadedFiles(['file' => $uploadedFile]));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['_redirect']], $response);
    }

    public function testRequestError()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('importPage')->willReturn('_redirect');

        $stream = $this->createStub(StreamInterface::class);

        $importer = $this->createMock(Importer::class);
        $importer->method('importStream')->willThrowException(new ImportError('import error'));

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getStream')->willReturn($stream);

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with(FlashMessages::Error, 'import error');

        $handler = $this->createHandler(
            routeHelper: $routeHelper,
            importer: $importer,
            flashMessages: $flashMessages,
        );
        $response = $handler->handle($this->request->withUploadedFiles(['file' => $uploadedFile]));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['_redirect']], $response);
    }
}
