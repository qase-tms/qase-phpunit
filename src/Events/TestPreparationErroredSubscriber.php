<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationErrored;
use PHPUnit\Event\Test\PreparationErroredSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestPreparationErroredSubscriber implements PreparationErroredSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(
        QaseReporterInterface $reporter
    )
    {
        $this->reporter = $reporter;
    }

    public function notify(PreparationErrored $event): void
    {
        $test = $event->test();

        if (!($test instanceof TestMethod)) {
            return;
        }

        $throwable = $event->throwable();

        $this->reporter->updateStatus($test, 'failed', $throwable->message(), $throwable->asString());
    }
}
