<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestPassedSubscriber implements PassedSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(
        QaseReporterInterface $reporter
    )
    {
        $this->reporter = $reporter;
    }

    public function notify(Passed $event): void
    {
        $test = $event->test();

        if (!($test instanceof TestMethod)) {
            return;
        }

        $this->reporter->updateStatus($test, 'passed');
    }
}
