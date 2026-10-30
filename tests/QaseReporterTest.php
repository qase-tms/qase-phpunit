<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\QaseReporter;

class QaseReporterTest extends TestCase
{
    public function testThrowableParameterIsReportedAsClassAndMessage(): void
    {
        $this->assertSame('RuntimeException: boom', $this->convertValueToString(new \RuntimeException('boom')));
    }

    public function testThrowableParameterWithoutMessageIsReportedAsClass(): void
    {
        $this->assertSame('RuntimeException', $this->convertValueToString(new \RuntimeException()));
    }

    public function testStringableParameterKeepsItsStringForm(): void
    {
        $value = new class {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        $this->assertSame('stringable', $this->convertValueToString($value));
    }

    private function convertValueToString(mixed $value): string
    {
        $reporter = (new \ReflectionClass(QaseReporter::class))->newInstanceWithoutConstructor();

        return (new \ReflectionMethod($reporter, 'convertValueToString'))->invoke($reporter, $value);
    }
}
