<?php

namespace Braintacle\Test\Validator;

use Braintacle\Validator\AssertIpAddress;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertIpAddress::class)]
final class AssertIpAddressTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testNullValid()
    {
        $validator = new AssertIpAddress();
        $validator->validate(null);
    }

    #[DoesNotPerformAssertions]
    public function testIpV4Valid()
    {
        $validator = new AssertIpAddress();
        $validator->validate('192.0.2.0');
    }

    #[DoesNotPerformAssertions]
    public function testIpV6Valid()
    {
        $validator = new AssertIpAddress();
        $validator->validate('2001:db8::');
    }

    public function testInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new AssertIpAddress();
        $validator->validate('');
    }
}
