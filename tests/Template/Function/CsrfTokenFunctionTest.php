<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\CsrfTokenFunction;
use Console\Validator\CsrfValidator;
use PHPUnit\Framework\TestCase;

class CsrfTokenFunctionTest extends TestCase
{
    public function testInvoke()
    {
        $csrfvalidator = $this->createStub(CsrfValidator::class);
        $csrfvalidator->method('getHash')->willReturn('token');

        $csrfTokenFunction = new CsrfTokenFunction($csrfvalidator);
        $this->assertEquals('token', $csrfTokenFunction());
    }
}
