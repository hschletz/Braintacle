<?php

namespace Braintacle\Test\Group;

use Braintacle\FlashMessages;
use Braintacle\Group\DeleteHandler;
use Braintacle\Group\Group;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Group\GroupManager;
use Model\Group\RuntimeException as GroupRuntimeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DeleteHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    private function getResponse(GroupManager $groupManager, FlashMessages $flashMessages)
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

        $handler = new DeleteHandler($this->response, $dataProcessor, $groupManager, $flashMessages, $translator);

        return $handler->handle($this->request->withQueryParams($queryParams));
    }

    public function testSuccess()
    {

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->once())->method('deleteGroup');

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages
            ->expects($this->once())
            ->method('add')
            ->with(FlashMessages::Success, "_Group 'groupName' was successfully deleted.");

        $response = $this->getResponse($groupManager, $flashMessages);
        $this->assertResponseStatusCode(200, $response);
    }

    public function testCatchableError()
    {
        $groupManager = $this->createStub(GroupManager::class);
        $groupManager->method('deleteGroup')->willThrowException(new GroupRuntimeException());

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->never())->method('add');

        $response = $this->getResponse($groupManager, $flashMessages);
        $this->assertResponseStatusCode(500, $response);
        $this->assertResponseHeaders(['Content-Type' => ['text/plain']], $response);
        $this->assertResponseContent("_Group 'groupName' could not be deleted. Try again later.", $response);
    }

    public function testOtherError()
    {
        $groupManager = $this->createStub(GroupManager::class);
        $groupManager->method('deleteGroup')->willThrowException(new RuntimeException('test'));

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->never())->method('add');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');
        $this->getResponse($groupManager, $flashMessages);
    }
}
