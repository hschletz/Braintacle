<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use Formotron\AssertionFailedException;
use Laminas\Session\Validator\Csrf;
use PHPUnit\Framework\TestCase;

class CsrfProcessorTest extends TestCase
{
    public function testMissingToken()
    {
        $csrfValidator = $this->createStub(Csrf::class);
        $csrfProcessor = new CsrfProcessor($csrfValidator);
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('csrfToken not set');
        $csrfProcessor->process(['foo' => 'bar']);
    }

    public function testInvalidToken()
    {
        $csrfValidator = $this->createMock(Csrf::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(false);
        $csrfValidator->method('getMessages')->willReturn(['token invalid']);
        $csrfProcessor = new CsrfProcessor($csrfValidator);
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('token invalid');
        $csrfProcessor->process(['foo' => 'bar', 'csrfToken' => 'token']);
    }

    public function testValidToken()
    {
        $csrfValidator = $this->createMock(Csrf::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(true);
        $csrfProcessor = new CsrfProcessor($csrfValidator);
        $this->assertEquals(
            ['foo' => 'bar'],
            $csrfProcessor->process(['foo' => 'bar', 'csrfToken' => 'token'])
        );
    }
}
