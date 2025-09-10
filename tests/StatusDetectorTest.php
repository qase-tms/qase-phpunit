<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Event\Code\Throwable as PHPUnitThrowable;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\StatusDetector;

class StatusDetectorTest extends TestCase
{
    public function testIsAssertionFailureWithAssertionFailedError(): void
    {
        $throwable = $this->createPHPUnitThrowable(AssertionFailedError::class, 'Test assertion failed');
        
        $this->assertTrue(StatusDetector::isAssertionFailure($throwable));
    }

    public function testIsAssertionFailureWithExpectationFailedException(): void
    {
        $throwable = $this->createPHPUnitThrowable(ExpectationFailedException::class, 'Expected value was not equal to actual value');
        
        $this->assertTrue(StatusDetector::isAssertionFailure($throwable));
    }

    public function testIsNotAssertionFailureWithGenericException(): void
    {
        $throwable = $this->createPHPUnitThrowable(\Exception::class, 'Generic exception occurred');
        
        $this->assertFalse(StatusDetector::isAssertionFailure($throwable));
    }

    public function testIsNotAssertionFailureWithRuntimeException(): void
    {
        $throwable = $this->createPHPUnitThrowable(\RuntimeException::class, 'Runtime error occurred');
        
        $this->assertFalse(StatusDetector::isAssertionFailure($throwable));
    }

    public function testGetStatusForFailureWithAssertionFailedError(): void
    {
        $throwable = $this->createPHPUnitThrowable(AssertionFailedError::class, 'Test assertion failed');
        
        $this->assertEquals('failed', StatusDetector::getStatusForFailure($throwable));
    }

    public function testGetStatusForFailureWithExpectationFailedException(): void
    {
        $throwable = $this->createPHPUnitThrowable(ExpectationFailedException::class, 'Expected value was not equal to actual value');
        
        $this->assertEquals('failed', StatusDetector::getStatusForFailure($throwable));
    }

    public function testGetStatusForFailureWithGenericException(): void
    {
        $throwable = $this->createPHPUnitThrowable(\Exception::class, 'Generic exception occurred');
        
        $this->assertEquals('invalid', StatusDetector::getStatusForFailure($throwable));
    }

    public function testGetStatusForFailureWithRuntimeException(): void
    {
        $throwable = $this->createPHPUnitThrowable(\RuntimeException::class, 'Runtime error occurred');
        
        $this->assertEquals('invalid', StatusDetector::getStatusForFailure($throwable));
    }

    private function createPHPUnitThrowable(string $className, string $message): PHPUnitThrowable
    {
        return new PHPUnitThrowable(
            $className,
            $message,
            $message,
            'Stack trace here',
            null
        );
    }
}
