<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Duplicates\AllowDuplicatesHandler;
use Braintacle\Duplicates\AllowDuplicatesRequestParameters;
use Braintacle\Duplicates\Criterion;
use Braintacle\FlashMessages;
use Braintacle\Test\HttpHandlerTestTrait;
use Exception;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\TestCase;

class AllowDuplicatesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testSuccess()
    {
        $value = 'AT';
        $parsedBody = ['criterion' => 'asset_tag', 'value' => $value];

        $formData = new AllowDuplicatesRequestParameters();
        $formData->criterion = Criterion::AssetTag;
        $formData->value = $value;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($parsedBody)->willReturn($formData);

        $duplicatesManager = $this->createMock(DuplicatesManager::class);
        $duplicatesManager->expects($this->once())->method('allow')->with(Criterion::AssetTag, $value);

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with(FlashMessages::Success, '_AT_');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with("'%s' is no longer considered duplicate.")->willReturn('_%s_');

        $handler = new AllowDuplicatesHandler(
            $this->response,
            $dataProcessor,
            $duplicatesManager,
            $flashMessages,
            $translator,
        );
        $response = $handler->handle($this->request->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(200, $response);
    }

    public function testException()
    {
        $formData = new AllowDuplicatesRequestParameters();
        $formData->criterion = Criterion::AssetTag;
        $formData->value = 'AT';

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturn($formData);

        $duplicatesManager = $this->createMock(DuplicatesManager::class);
        $duplicatesManager->method('allow')->willThrowException(new Exception('test'));

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->never())->method('add');

        $translator = $this->createStub(TranslatorInterface::class);

        $handler = new AllowDuplicatesHandler(
            $this->response,
            $dataProcessor,
            $duplicatesManager,
            $flashMessages,
            $translator,
        );

        $this->expectExceptionMessage('test');
        $handler->handle($this->request->withParsedBody([]));
    }
}
