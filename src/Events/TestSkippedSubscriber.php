<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Events;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use Qase\PHPUnitReporter\QaseReporterInterface;

final class TestSkippedSubscriber implements SkippedSubscriber
{
    private QaseReporterInterface $reporter;

    public function __construct(
        QaseReporterInterface $reporter
    )
    {
        $this->reporter = $reporter;
    }

    public function notify(Skipped $event): void
    {
        $test = $event->test();

        if (!($test instanceof TestMethod)) {
            return;
        }

        $this->reporter->updateStatus($test, 'skipped', $event->message());
    }
}
