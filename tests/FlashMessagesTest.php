<?php

namespace Braintacle\Test;

use Braintacle\FlashMessages;
use Laminas\Session\Container;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FlashMessagesTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionTestTrait;

    public function testAdd()
    {
        $session = $this->createSession();
        $flashMessages = new FlashMessages($session);
        $flashMessages->add('key1', 'message1');
        $flashMessages->add('key1', 'message2');
        $flashMessages->add('key2', 'message3');

        $this->assertEquals([
            FlashMessages::class => [
                'key1' => ['message1', 'message2'],
                'key2' => ['message3'],
            ],
        ], $session->getArrayCopy());
    }

    public function testAddSetsExpirationHops()
    {
        // Order is significant: setExpirationHops() would have no effect if called before offsetSet().
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('offsetExists');
        $container->shouldReceive('offsetSet')->once()->ordered();
        $container->shouldReceive('setExpirationHops')->once()->ordered()->with(1, FlashMessages::class);

        $flashMessages = new FlashMessages($container);
        $flashMessages->add('test', 'message');
    }

    public function testGet()
    {
        $session = $this->createSession();
        $session[FlashMessages::class] = [
            'key1' => ['message1', 'message2'],
            'key2' => ['message3'],
        ];
        $flashMessages = new FlashMessages($session);

        $this->assertEquals(['message1', 'message2'], $flashMessages->get('key1'));
        $this->assertEquals(['message3'], $flashMessages->get('key2'));
        $this->assertEquals([], $flashMessages->get('key3'));
    }
}
