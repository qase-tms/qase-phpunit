<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\QaseId;

class ExampleTest extends TestCase
{
    #[QaseId(1)]
    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    #[QaseId(2)]
    public function testSkipped(): void
    {
        $this->markTestSkipped('Intentionally skipped');
    }

    #[QaseId(3)]
    public function testFail(): void
    {
        $this->assertTrue(false);
    }
}
