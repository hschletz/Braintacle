<?php

namespace Console\Test\Validator;

use Console\Validator\CsrfValidator;
use PHPUnit\Framework\TestCase;

class CsrfValidatorTest extends TestCase
{
    public function testDefaultTimeout()
    {
        $validator = new CsrfValidator();
        $this->assertNull($validator->getTimeout());
    }

    public function testGetToken()
    {
        $token = CsrfValidator::getToken();
        $this->assertNotEmpty($token);
    }
}
