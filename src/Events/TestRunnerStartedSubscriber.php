<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;


use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestRunnerStartedSubscriber implements StartedSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(QaseReporterInterface $reporter)
    {
        $this->reporter = $reporter;
    }

    public function notify(Started $event): void
    {
        $this->reporter->startTestRun();
    }
}
