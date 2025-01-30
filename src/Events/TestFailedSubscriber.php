<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestFailedSubscriber implements FailedSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(
        QaseReporterInterface $reporter
    )
    {
        $this->reporter = $reporter;
    }

    public function notify(Failed $event): void
    {
        $test = $event->test();

        if (!($test instanceof TestMethod)) {
            return;
        }

        $this->reporter->updateStatus($test, 'failed', $event->throwable()->message(), $event->throwable()->asString());
    }
}
