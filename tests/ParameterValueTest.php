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

    public function testValueThatChangesBetweenTwoProviderRunsBecomesOpaque(): void
    {
        $stable = ParameterValue::stableOnly(
            ['days' => '3', 'token' => 'a1', 'gone' => 'x'],
            ['days' => '3', 'token' => 'b2'],
        );

        $this->assertSame(['days' => '3', 'token' => ParameterValue::OPAQUE, 'gone' => ParameterValue::OPAQUE], $stable);
    }

    public function testLongValueIsCutAfterTheLimit(): void
    {
        $long = str_repeat('я', ParameterValue::MAX_LENGTH + 1);

        $this->assertSame(str_repeat('я', ParameterValue::MAX_LENGTH) . '...', ParameterValue::toString($long));
    }

    public function testValueAtTheLimitIsKeptWhole(): void
    {
        $exact = str_repeat('a', ParameterValue::MAX_LENGTH);

        $this->assertSame($exact, ParameterValue::toString($exact));
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
