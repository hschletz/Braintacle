<?php

namespace Braintacle\Test\Client\Registry;

use Braintacle\Client\Registry\AssembleKey;
use Braintacle\Client\Registry\RootKey;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(AssembleKey::class)]
final class AssembleKeyTest extends TestCase
{
    public function testMissingRootKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing regtree');

        $assembleKey = new AssembleKey();
        $assembleKey->process([
            'regkey' => 'subKeys',
            'value_name' => 'valueName',
        ]);
    }

    public function testMissingSubKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing regkey');

        (new AssembleKey())->process([
            'regtree' => RootKey::HKEY_LOCAL_MACHINE->value,
            'value_name' => 'valueName',
        ]);
    }

    public function testMissingValueName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing value_name');

        (new AssembleKey())->process([
            'regtree' => RootKey::HKEY_LOCAL_MACHINE->value,
            'regkey' => 'subKeys',
        ]);
    }
    public function testInvalidRootKey()
    {
        $this->expectException(ValueError::class);

        (new AssembleKey())->process([
            'regtree' => '-1',
            'regkey' => 'subKeys',
            'value_name' => 'valueName',
        ]);
    }

    public function testProcess()
    {
        $this->assertEquals(
            [
                'name' => 'name',
                'path' => 'HKEY_LOCAL_MACHINE\subKeys\valueName',
            ],
            (new AssembleKey())->process([
                'name' => 'name',
                'regtree' => RootKey::HKEY_LOCAL_MACHINE->value,
                'regkey' => 'subKeys',
                'value_name' => 'valueName',
            ]),
        );
    }
}
