<?php

namespace Braintacle\Test;

use Mockery;
use Mockery\MockInterface;

/**
 * Wrappers for Mockery::mock().
 *
 * Mockery 1.6.13 removed the generic return type for Mockery::mock(), which is
 * now annotated to return MockInterface only, without any reference to the
 * mocked class. This causes Psalm to warn about an invalid argument type when
 * the mock is passed to a function with a class type hint. Every mock would
 * have to be annotated manually to provide the actual type information.
 *
 * This class provides some wrapper methods which restore the generic type
 * information. Instead of overloading a single method with all kinds of
 * parameters for different purposes, like Mockery::mock() does, specialized
 * methods are provided for better typing.
 */
final class MockeryWrapper
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return MockInterface|T
     */
    public static function createMock(string $class)
    {
        return Mockery::mock($class);
    }
}
