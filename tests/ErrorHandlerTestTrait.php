<?php

namespace Braintacle\Test;

use ErrorException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PostCondition;

/**
 * Trait for asserting error handler restoration.
 *
 * This can be used to test code that sets a temporary error handler and
 * restores the previous handler when finished. This trait verifies that the
 * restoration has happened after each test.
 */
trait ErrorHandlerTestTrait
{
    #[Before]
    public function setErrorHandler()
    {
        // Set up a handler that throws a distinct exception which will be
        // caught and tested in assertErrorHandlerRestored().
        set_error_handler(
            function (int $errno, string $errstr) {
                throw new ErrorException(__TRAIT__ . $errstr, $errno);
            },
            E_USER_WARNING
        );
    }

    #[After]
    public function restoreErrorHandler()
    {
        restore_error_handler();
    }

    #[PostCondition]
    protected function assertErrorHandlerRestored(): void
    {
        // Trigger a warning. The handler that gets installed before each test
        // should throw a distinct exception which will be caught and tested. If
        // the tested code does not restore the handler, this will not happen, a
        // situation which will be detected here.
        $message = null;
        try {
            trigger_error(' handler restored', E_USER_WARNING);
        } catch (ErrorException $warning) {
            $message = $warning->getMessage();
            if ($message != __TRAIT__ . ' handler restored') {
                $this->fail('Tested code did not restore error handler.');
            }
        }
        if (!$message) {
            $this->fail(__TRAIT__ . ' set up an error handler, but it did not get invoked.');
        }
    }
}
