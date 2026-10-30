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

    public function testMockeryMockIsOpaque(): void
    {
        $this->assertSame(ParameterValue::OPAQUE, ParameterValue::toString(\Mockery::mock(\Countable::class)));
    }

    public function testPhpUnitMockIsOpaque(): void
    {
        $this->assertSame(ParameterValue::OPAQUE, ParameterValue::toString($this->createMock(\Countable::class)));
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
