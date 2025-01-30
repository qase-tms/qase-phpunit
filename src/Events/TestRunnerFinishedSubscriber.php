<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;


use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestRunnerFinishedSubscriber implements FinishedSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(QaseReporterInterface $reporter)
    {
        $this->reporter = $reporter;
    }

    public function notify(Finished $event): void
    {
        $this->reporter->completeTestRun();
    }
}
