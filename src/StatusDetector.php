<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

use PHPUnit\Event\Code\Throwable as PHPUnitThrowable;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class for detecting test failure types to determine appropriate status
 */
class StatusDetector
{
    /**
     * Determine if the throwable represents an assertion failure
     * 
     * @param PHPUnitThrowable $throwable The exception/error that caused test failure
     * @return bool True if it's an assertion failure, false otherwise
     */
    public static function isAssertionFailure(PHPUnitThrowable $throwable): bool
    {
        $className = $throwable->className();
        
        return $className === AssertionFailedError::class || 
               $className === ExpectationFailedException::class ||
               is_subclass_of($className, AssertionFailedError::class) ||
               is_subclass_of($className, ExpectationFailedException::class);
    }

    /**
     * Get the appropriate status for a test failure
     * 
     * @param PHPUnitThrowable $throwable The exception/error that caused test failure
     * @return string 'failed' for assertion failures, 'invalid' for other errors
     */
    public static function getStatusForFailure(PHPUnitThrowable $throwable): string
    {
        return self::isAssertionFailure($throwable) ? 'failed' : 'invalid';
    }
}
