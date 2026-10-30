<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\ParameterValue;

class ParameterValueTest extends TestCase
{
    public function testThrowableIsReportedAsClassAndMessage(): void
    {
        $this->assertSame('RuntimeException: boom', ParameterValue::toString(new \RuntimeException('boom')));
    }

    public function testThrowableWithoutMessageIsReportedAsClass(): void
    {
        $this->assertSame('RuntimeException', ParameterValue::toString(new \RuntimeException()));
    }

    public function testPhpUnitMockOfAnInterfaceIsNamedByTheInterface(): void
    {
        $this->assertSame('mock of Countable', ParameterValue::toString($this->createMock(\Countable::class)));
    }

    public function testPhpUnitMockOfAClassIsNamedByTheClass(): void
    {
        $this->assertSame('mock of ArrayObject', ParameterValue::toString($this->createMock(\ArrayObject::class)));
    }

    public function testMockOfAnExtendedInterfaceIsNamedByTheMostSpecificOne(): void
    {
        $mock = $this->createMock(\IteratorAggregate::class);

        $this->assertSame('mock of IteratorAggregate', ParameterValue::toString($mock));
    }

    public function testStringableObjectKeepsItsStringForm(): void
    {
        $value = new class {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        $this->assertSame('stringable', ParameterValue::toString($value));
    }
}
