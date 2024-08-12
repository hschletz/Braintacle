<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\CsrfTokenFunction;
use Laminas\Validator\Csrf;
use PHPUnit\Framework\TestCase;

class CsrfTokenFunctionTest extends TestCase
{
    public function testInvoke()
    {
        $csrfvalidator = $this->createStub(Csrf::class);
        $csrfvalidator->method('getHash')->willReturn('token');

        $csrfTokenFunction = new CsrfTokenFunction($csrfvalidator);
        $this->assertEquals('token', $csrfTokenFunction());
    }
}
