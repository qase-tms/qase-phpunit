<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Attributes\Suite;
use Qase\PHPUnitReporter\Qase;

class CombinedTest extends TestCase
{
    #[QaseId(40)]
    public function testWithComment(): void
    {
        Qase::comment("This is a test comment");
        $this->assertTrue(true);
    }

    #[QaseId(41)]
    public function testWithDynamicTitle(): void
    {
        Qase::title("Overridden title at runtime");
        $this->assertTrue(true);
    }

    #[QaseId(42), Suite("Integration"), Field("component", "auth")]
    public function testWithSuiteAndField(): void
    {
        $this->assertTrue(true);
    }
}
