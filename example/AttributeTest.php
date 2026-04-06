<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Attributes\QaseIds;
use Qase\PHPUnitReporter\Attributes\Suite;
use Qase\PHPUnitReporter\Attributes\Title;

class AttributeTest extends TestCase
{
    #[QaseId(10)]
    public function testWithQaseId(): void
    {
        $this->assertTrue(true);
    }

    #[QaseIds([11, 12])]
    public function testWithQaseIds(): void
    {
        $this->assertTrue(true);
    }

    #[QaseId(13), Title("Custom title for test")]
    public function testWithTitle(): void
    {
        $this->assertTrue(true);
    }

    #[QaseId(14), Suite("Auth"), Suite("Login")]
    public function testWithSuites(): void
    {
        $this->assertTrue(true);
    }

    #[
        QaseId(15),
        Field("severity", "critical"),
        Field("layer", "unit")
    ]
    public function testWithFields(): void
    {
        $this->assertTrue(true);
    }

    #[
        QaseId(16),
        Title("Full attributes test"),
        Suite("Smoke"),
        Field("priority", "high")
    ]
    public function testWithAllAttributes(): void
    {
        $this->assertTrue(true);
    }
}
