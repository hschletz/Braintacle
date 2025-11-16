<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Client\Duplicates;
use Braintacle\CsrfProcessor;
use Braintacle\Duplicates\MergeDuplicatesHandler;
use Braintacle\FlashMessages;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TranslatorStubTrait;
use Console\Form\ShowDuplicates as Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class MergeDuplicatesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;
    use TranslatorStubTrait;

    private function getResponse(bool $isValid): ResponseInterface
    {
        $formData = ['key' => 'value'];
        $parsedBody = $formData + ['csrfToken' => 'token'];

        $clients = [1, 2];
        $options = ['mergeCustomFields', 'mergePackages'];

        $validatedData = ['clients' => $clients, 'mergeOptions' => $options];

        $csrfProcessor = $this->createMock(CsrfProcessor::class);
        $csrfProcessor->method('process')->with($parsedBody)->willReturn($formData);

        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('setData')->with($formData);
        $validator->method('isValid')->willReturn($isValid);
        $validator->method('getData')->willReturn($validatedData);
        $validator->method('getMessages')->willReturn(['level1' => ['level2' => 'message']]);

        $duplicates = $this->createMock(Duplicates::class);
        $flashMessages = $this->createMock(FlashMessages::class);
        $message = '_The selected clients have been merged.';

        if ($isValid) {
            $duplicates->expects($this->once())->method('merge')->with($clients, $options);
            $flashMessages->expects($this->once())->method('add')->with(FlashMessages::Success, $message);
        } else {
            $duplicates->expects($this->never())->method('merge');
            $flashMessages->expects($this->never())->method('add');
        }

        $translator = $this->createTranslatorStub();

        $handler = new MergeDuplicatesHandler(
            $this->response,
            $csrfProcessor,
            $validator,
            $duplicates,
            $flashMessages,
            $translator,
        );

        return $handler->handle($this->request->withParsedBody($parsedBody));
    }

    public function testValid()
    {
        $response = $this->getResponse(true);
        $this->assertResponseStatusCode(200, $response);
    }

    public function testInvalid()
    {
        $response = $this->getResponse(false);
        $this->assertResponseStatusCode(400, $response);
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals(['message'], json_decode($this->getMessageContent($response), true));
    }
}
