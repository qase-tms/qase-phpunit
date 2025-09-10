<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;
use Qase\PHPUnitReporter\StatusDetector;

final class TestErroredSubscriber implements ErroredSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(
        QaseReporterInterface $reporter
    )
    {
        $this->reporter = $reporter;
    }

    public function notify(Errored $event): void
    {
        $test = $event->test();

        if (!($test instanceof TestMethod)) {
            return;
        }

        $throwable = $event->throwable();
        $status = StatusDetector::getStatusForFailure($throwable);

        $this->reporter->updateStatus($test, $status, $throwable->message(), $throwable->asString());
    }
}
