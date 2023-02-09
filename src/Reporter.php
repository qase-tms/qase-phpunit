<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSkippedTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use Qase\PhpCommons\Config\BaseConfig;
use Qase\PhpCommons\Config\ReportConfig;
use Qase\PhpCommons\Loggers\ConsoleLogger;
use Qase\PhpCommons\Loggers\NullLogger;
use Qase\PhpCommons\Reporters\Report;
use Qase\PhpCommons\Reporters\TestOps;
use Qase\PhpCommons\Config\TestOpsConfig;
use Qase\PhpCommons\Interfaces\ReporterInterface;
use Qase\PhpCommons\Models\Result;

class Reporter implements AfterSuccessfulTestHook, AfterSkippedTestHook, AfterTestFailureHook, AfterTestErrorHook, AfterLastTestHook, BeforeFirstTestHook, BeforeTestHook
{
    private const ROOT_SUITE_TITLE = 'PHPUnit tests';

    private const REPORTER_NAME = 'PHPUnit';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    public const FAILED = 'failed';
    public const INVALID = 'invalid';

    private HeaderManager $headerManager;

    private ReporterInterface $reporter;

    private bool $isEnabled = false;

    private BaseConfig $config;

    private $logger;

    private Result $currentResult;

    public function __construct()
    {
        if (isset($_ENV['QASE_DEBUG'])) {
            $this->logger = new ConsoleLogger();
        } else {
            $this->logger = new NullLogger();
        }
        
        if (!isset($_ENV['QASE_MODE'])) {
            $this->logger->writeln('Reporting to Qase.io is disabled. Set the environment variable QASE_MODE=testops or QASE_MODE=report to enable it.');
            return;
        }

        try {
            switch ($_ENV['QASE_MODE']) {
                case "testops":
                    $this->config = new TestOpsConfig(self::REPORTER_NAME, $this->logger, new HeaderManager());
                    $this->reporter = new TestOps($this->config);
                    break;
                case "report":
                    $this->config = new ReportConfig(self::REPORTER_NAME, $this->logger);
                    $this->reporter = new Report($this->config);
                    break;
                default:
                    $this->logger->writeln('Invalid Qase Reporter mode. Set the environment variable QASE_MODE=testops or QASE_MODE=report.');
                    return;
            }
            $this->isEnabled = true;
        } catch (\Exception $e) {
            $this->logger->writeln($e->getMessage());
        }
    }
    
    public function executeBeforeFirstTest(): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->reporter->startRun();
    }

    public function executeBeforeTest(string $test): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->currentResult = new Result($test);
    }

    public function executeAfterSkippedTest(string $test, string $message, float $time): void
    {
        $this->currentResult->status = self::SKIPPED;
        $this->currentResult->duration = (int)floor($time*1000);
        $this->currentResult->comment = $message;
        $this->reporter->addResult($this->currentResult);
    }

    public function executeAfterSuccessfulTest(string $test, float $time): void
    {
        $this->currentResult->status = self::PASSED;
        $this->currentResult->duration = (int)floor($time*1000);
        $this->reporter->addResult($this->currentResult);
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        $this->currentResult->status = self::FAILED;
        $this->currentResult->duration = (int)floor($time*1000);
        $this->currentResult->comment = $message;
        $this->reporter->addResult($this->currentResult);
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        $this->currentResult->status = self::INVALID;
        $this->currentResult->duration = (int)floor($time*1000);
        $this->currentResult->comment = $message;
        $this->reporter->addResult($this->currentResult);
    }

    public function executeAfterLastTest(): void
    {
        $this->reporter->completeRun();
    }
}
