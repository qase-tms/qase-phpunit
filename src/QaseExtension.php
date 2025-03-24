<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Qase\PhpCommons\Loggers\Logger;
use Qase\PhpCommons\Reporters\ReporterFactory;
use Qase\PHPUnitReporter\Attributes\AttributeParser;
use Qase\PHPUnitReporter\Attributes\AttributeReader;

final class QaseExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $logger = new Logger();
        $coreReporter = ReporterFactory::create("phpunit/phpunit", "qase/phpunit-reporter");
        $attributeReader = new AttributeReader();
        $attributeParser = new AttributeParser($logger, $attributeReader);

        $reporter = QaseReporter::getInstance($attributeParser, $coreReporter);
        
        $facade->registerSubscribers(
            new Events\TestConsideredRiskySubscriber($reporter),
            new Events\TestPreparedSubscriber($reporter),
            new Events\TestFinishedSubscriber($reporter),
            new Events\TestFailedSubscriber($reporter),
            new Events\TestErroredSubscriber($reporter),
            new Events\TestMarkedIncompleteSubscriber($reporter),
            new Events\TestSkippedSubscriber($reporter),
            new Events\TestWarningTriggeredSubscriber($reporter),
            new Events\TestConsideredRiskySubscriber($reporter),
            new Events\TestPassedSubscriber($reporter),
            new Events\TestRunnerFinishedSubscriber($reporter),
            new Events\TestRunnerStartedSubscriber($reporter),
        );
    }
}
