<?php

namespace Braintacle\Test\Group;

use Braintacle\FlashMessages;
use Braintacle\Group\DeleteHandler;
use Braintacle\Group\Group;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\Groups;
use Braintacle\Test\HttpHandlerTestTrait;
use Exception;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DeleteHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    private function getResponse(Groups $groups, FlashMessages $flashMessages)
    {
        $groupName = 'groupName';
        $queryParams = ['name' => $groupName];

        $group = new Group();
        $group->name = $groupName;

        $requestParameters = new GroupRequestParameters();
        $requestParameters->group = $group;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams)->willReturn($requestParameters);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn($message) => '_' . $message);

        $handler = new DeleteHandler($this->response, $dataProcessor, $groups, $flashMessages, $translator);

        return $handler->handle($this->request->withQueryParams($queryParams));
    }

    public function testSuccess()
    {

        $groups = $this->createMock(Groups::class);
        $groups->expects($this->once())->method('deleteGroup');

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages
            ->expects($this->once())
            ->method('add')
            ->with(FlashMessages::Success, "_Group 'groupName' was successfully deleted.");

        $response = $this->getResponse($groups, $flashMessages);
        $this->assertResponseStatusCode(200, $response);
    }

    public function testCatchableError()
    {
        $groups = $this->createStub(Groups::class);
        $groups->method('deleteGroup')->willThrowException(new RuntimeException());

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->never())->method('add');

        $response = $this->getResponse($groups, $flashMessages);
        $this->assertResponseStatusCode(500, $response);
        $this->assertResponseHeaders(['Content-Type' => ['text/plain']], $response);
        $this->assertResponseContent("_Group 'groupName' could not be deleted. Try again later.", $response);
    }

    public function testOtherError()
    {
        $groups = $this->createStub(Groups::class);
        $groups->method('deleteGroup')->willThrowException(new Exception('test'));

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->never())->method('add');

        $this->expectExceptionMessage('test');
        $this->getResponse($groups, $flashMessages);
    }
}
