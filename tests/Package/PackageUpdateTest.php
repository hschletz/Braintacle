<?php

namespace Braintacle\Test\Package;

use Braintacle\Package\Action;
use Braintacle\Package\Build\FormValidator;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use Braintacle\Package\Platform;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertNumericRange;
use Braintacle\Validator\AssertStringLength;
use Exception;
use Formotron\PostProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageUpdate::class)]
#[UsesClass(Package::class)]
#[UsesClass(AssertNumericRange::class)]
#[UsesClass(AssertStringLength::class)]
#[UsesClass(TrimAndNullify::class)]
final class PackageUpdateTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    private function process(array $input): PackageUpdate
    {
        $formValidator = $this->createMock(PostProcessor::class);
        $formValidator->expects($this->once())->method('process');

        return $this->createDataProcessor([FormValidator::class => $formValidator])->process(
            $input,
            PackageUpdate::class,
        );
    }

    private function createMinimalData(): array
    {
        return [
            'name' => 'N',
            'comment' => '',
            'platform' => 'linux',
            'action' => 'execute',
            'actionParam' => 'param',
            'priority' => '0',
            'maxFragmentSize' => '0',
            'warnMessage' => '',
            'warnCountdown' => '',
            'postInstMessage' => '',
        ];
    }

    public function testMinimalData()
    {
        $package = $this->process($this->createMinimalData());
        $this->assertEquals('N', $package->name);
        $this->assertNull($package->comment);
        $this->assertEquals(Platform::Linux, $package->platform);
        $this->assertEquals(Action::Execute, $package->action);
        $this->assertEquals('param', $package->actionParam);
        $this->assertEquals(0, $package->priority);
        $this->assertEquals(0, $package->maxFragmentSize);
        $this->assertFalse($package->warn);
        $this->assertNull($package->warnMessage);
        $this->assertNull($package->warnCountdown);
        $this->assertFalse($package->warnAllowAbort);
        $this->assertFalse($package->warnAllowDelay);
        $this->assertNull($package->postInstMessage);
        $this->assertFalse($package->deployPending);
        $this->assertFalse($package->deployRunning);
        $this->assertFalse($package->deploySuccess);
        $this->assertFalse($package->deployError);
        $this->assertFalse($package->deployGroups);
    }

    #[TestWith(['comment', ' ', null])]
    #[TestWith(['comment', ' _comment ', '_comment'])]
    #[TestWith(['priority', '10', 10])]
    #[TestWith(['warn', 'on', true])]
    #[TestWith(['warnMessage', ' ', null])]
    #[TestWith(['warnMessage', ' message ', 'message'])]
    #[TestWith(['warnCountdown', ' 1 ', 1])]
    #[TestWith(['warnAllowAbort', 'on', true])]
    #[TestWith(['warnAllowDelay', 'on', true])]
    #[TestWith(['postInstMessage', ' ', null])]
    #[TestWith(['postInstMessage', ' message ', 'message'])]
    #[TestWith(['deployPending', 'on', true])]
    #[TestWith(['deployRunning', 'on', true])]
    #[TestWith(['deploySuccess', 'on', true])]
    #[TestWith(['deployError', 'on', true])]
    #[TestWith(['deployGroups', 'on', true])]
    public function testAdditionalValidData(string $key, string $value, mixed $expected)
    {
        $input = $this->createMinimalData();
        $input[$key] = $value;

        $package = $this->process($input);
        $this->assertSame($expected, $package->$key);
    }

    #[TestWith(['name', ''])]
    #[TestWith(['platform', ''])]
    #[TestWith(['action', ''])]
    #[TestWith(['actionParam', ''])]
    #[TestWith(['priority', '-1'])]
    #[TestWith(['priority', '11'])]
    #[TestWith(['priority', '1a'])]
    #[TestWith(['maxFragmentSize', '-1'])]
    #[TestWith(['maxFragmentSize', '1a'])]
    #[TestWith(['warnCountDown', '0'])]
    public function testInvalidData(string $key, string $value)
    {
        $input = $this->createMinimalData();
        $input[$key] = $value;

        $this->expectException(Exception::class);
        $this->createDataProcessor()->process($input, PackageUpdate::class);
    }

    public function testNameMaxLength()
    {
        $input = $this->createMinimalData();
        $input['name'] = str_repeat('Ä', 255);

        $this->process($input);
    }

    public function testNameMaxLengthExceeded()
    {
        $input = $this->createMinimalData();
        $input['name'] = str_repeat('a', 256);

        $this->expectException(Exception::class);
        $this->createDataProcessor()->process($input, PackageUpdate::class);
    }

    #[TestWith([42, 42])]
    #[TestWith([null, ''])]
    public function testToArray(?int $warnCountdown, int | string $expectedWarnCountdown)
    {
        $packageUpdate = new PackageUpdate();
        $packageUpdate->name = '_name';
        $packageUpdate->comment = '_comment';
        $packageUpdate->platform = Platform::Linux;
        $packageUpdate->action = Action::Execute;
        $packageUpdate->actionParam = '_actionParam';
        $packageUpdate->priority = 5;
        $packageUpdate->maxFragmentSize = 42;
        $packageUpdate->warn = true;
        $packageUpdate->warnCountdown = $warnCountdown;
        $packageUpdate->warnMessage = '_warnMessage';
        $packageUpdate->warnAllowAbort = false;
        $packageUpdate->warnAllowDelay = true;
        $packageUpdate->postInstMessage = '_postInstMessage';

        $this->assertSame(
            [
                'Name' => '_name',
                'Comment' => '_comment',
                'Platform' => 'linux',
                'DeployAction' => 'execute',
                'ActionParam' => '_actionParam',
                'Priority' => 5,
                'MaxFragmentSize' => 42,
                'Warn' => true,
                'WarnCountdown' => $expectedWarnCountdown,
                'WarnMessage' => '_warnMessage',
                'WarnAllowAbort' => false,
                'WarnAllowDelay' => true,
                'PostInstMessage' => '_postInstMessage',
            ],
            $packageUpdate->toArray(),
        );
    }
}
