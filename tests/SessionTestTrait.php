<?php

namespace Braintacle\Test;

use Laminas\Session\Container;
use Laminas\Session\ManagerInterface;
use Laminas\Session\Storage\ArrayStorage;

/**
 * Utility trait for testing session handling.
 */
trait SessionTestTrait
{
    /**
     * Create a fully functional session container.
     *
     * A real session container allows testing actual ArrayObject behavior with
     * all its pitfalls, which may be missed if replaced with a stub. All data
     * is confined to the session container. None of PHP's session functions are
     * invoked, avoiding any data leaking into or out of the test.
     */
    private function createSession(): Container
    {
        // ArrayStorage can safely be instantiated because it holds all the data
        // without interacting with the session.
        $storage = new ArrayStorage();

        // The session manager implementation contains calls to PHP's session
        // functions and must therefore be stubbed.
        $manager = $this->createStub(ManagerInterface::class);
        $manager->method('getStorage')->willReturn($storage);

        // The session container can safely be instantiated because it delegates
        // actual session handling to the session manager stub.
        return new Container(manager: $manager);
    }
}
