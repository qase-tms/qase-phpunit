<?php

namespace Qase\PHPUnitReporter;

use PHPUnit\Event\Code\TestMethod;

interface QaseReporterInterface
{
    public function startTestRun(): void;

    public function completeTestRun(): void;

    public function startTest(TestMethod $test): void;

    public function updateStatus(TestMethod $test, string $status, ?string $message = null, ?string $stackTrace = null): void;

    public function completeTest(TestMethod $test): void;
}
