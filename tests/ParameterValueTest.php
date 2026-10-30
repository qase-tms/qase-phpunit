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

    public function testMockeryMockOfAnInterfaceIsNamedByTheInterface(): void
    {
        $this->assertSame('mock of Countable', ParameterValue::toString(\Mockery::mock(\Countable::class)));
    }

    public function testMockeryMockOfAClassIsNamedByTheClass(): void
    {
        $this->assertSame('mock of ArrayObject', ParameterValue::toString(\Mockery::mock(\ArrayObject::class)));
    }

    public function testMockOfAnExtendedInterfaceIsNamedByTheMostSpecificOne(): void
    {
        $mock = \Mockery::mock(\IteratorAggregate::class);

        $this->assertSame('mock of IteratorAggregate', ParameterValue::toString($mock));
    }

    public function testPhpUnitMockOfAnInterfaceIsNamedByTheInterface(): void
    {
        $this->assertSame('mock of Countable', ParameterValue::toString($this->createMock(\Countable::class)));
    }

    public function testPhpUnitMockOfAClassIsNamedByTheClass(): void
    {
        $this->assertSame('mock of ArrayObject', ParameterValue::toString($this->createMock(\ArrayObject::class)));
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
