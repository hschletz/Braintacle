<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\CsrfProcessor;
use Braintacle\Duplicates\MergeDuplicatesHandler;
use Braintacle\Test\HttpHandlerTestTrait;
use Console\Form\ShowDuplicates as Validator;
use Laminas\Session\Container as Session;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class MergeDuplicatesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

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

        $duplicatesManager = $this->createMock(DuplicatesManager::class);
        $session = $this->createMock(Session::class);

        if ($isValid) {
            $duplicatesManager->expects($this->once())->method('merge')->with($clients, $options);
            $session->expects($this->once())->method('offsetSet')->with(MergeDuplicatesHandler::class, true);
            $session->expects($this->once())->method('setExpirationHops')->with(1, MergeDuplicatesHandler::class);
        } else {
            $duplicatesManager->expects($this->never())->method('merge');
            $session->expects($this->never())->method('offsetSet');
        }

        $handler = new MergeDuplicatesHandler($this->response, $csrfProcessor, $validator, $duplicatesManager, $session);

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
