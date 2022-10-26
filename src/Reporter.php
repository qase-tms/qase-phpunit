<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSkippedTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Qase\Client\ApiException;
use Qase\PhpClientUtils\Config;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\NullLogger;
use Qase\PhpClientUtils\Repository;
use Qase\PhpClientUtils\ResultHandler;
use Qase\PhpClientUtils\RunResult;
use Qase\PhpClientUtils\ResultsConverter;

class Reporter implements AfterSuccessfulTestHook, AfterSkippedTestHook, AfterTestFailureHook, AfterTestErrorHook, AfterLastTestHook, BeforeFirstTestHook
{
    private const ROOT_SUITE_TITLE = 'PHPUnit tests';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    private Repository $repo;
    private ResultHandler $resultHandler;
    private LoggerInterface $logger;
    private Config $config;
    private HeaderManager $headerManager;
    private RunResultCollection $runResultCollection;

    public function __construct()
    {
        $this->config = new Config('PHPUnit');
        if ($this->config->isLoggingEnabled()) {
            $this->logger = new ConsoleLogger();
        } else {
            $this->logger = new NullLogger();
        }
        $resultsConverter = new ResultsConverter($this->logger);

        if (!$this->config->isReportingEnabled()) {
            $this->logger->writeln('Reporting to Qase.io is disabled. Set the environment variable QASE_REPORT=1 to enable it.');
            return;
        }

        $this->headerManager = new HeaderManager();

        $this->config->validate();

        $runResult = new RunResult($this->config);

        $this->runResultCollection = new RunResultCollection($runResult, $this->config->isReportingEnabled());

        $this->repo = new Repository();
        $this->resultHandler = new ResultHandler($this->repo, $resultsConverter, $this->logger);
    }

    /**
     * @throws ApiException
     */
    public function executeBeforeFirstTest(): void
    {
        if (!$this->config->isReportingEnabled()) {
            return;
        }

        $this->repo->init(
            $this->config,
            $this->headerManager->getClientHeaders()
        );

        $this->validateProjectCode();
        $this->validateEnvironmentId();
    }

    public function executeAfterSkippedTest(string $test, string $message, float $time): void
    {
        $this->runResultCollection->add(self::SKIPPED, $test, $time, $message);
    }

    public function executeAfterSuccessfulTest(string $test, float $time): void
    {
        $this->runResultCollection->add(self::PASSED, $test, $time);
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        $this->runResultCollection->add(self::FAILED, $test, $time, $message);
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        $this->runResultCollection->add(self::FAILED, $test, $time, $message);
    }

    public function executeAfterLastTest(): void
    {
        if (!$this->config->isReportingEnabled()) {
            return;
        }

        try {
            $this->resultHandler->handle(
                $this->runResultCollection->get(),
                $this->config->getRootSuiteTitle() ?: self::ROOT_SUITE_TITLE,
            );
        } catch (\Exception $e) {
            $this->logger->writeln('An exception occurred');
            $this->logger->writeln($e->getMessage());

            return;
        }
    }

    /**
     * @throws ApiException
     */
    private function validateProjectCode(): void
    {
        try {
            $this->logger->write("checking if project '{$this->config->getProjectCode()}' exists... ");

            $this->repo->getProjectsApi()->getProject($this->config->getProjectCode());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find project '{$this->config->getProjectCode()}'");

            throw $e;
        }
    }

    /**
     * @throws ApiException
     */
    private function validateEnvironmentId(): void
    {
        if ($this->config->getEnvironmentId() === null) {
            return;
        }

        try {
            $this->logger->write("checking if Environment Id '{$this->config->getEnvironmentId()}' exists... ");

            $this->repo->getEnvironmentsApi()->getEnvironment($this->config->getProjectCode(), $this->config->getEnvironmentId());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find Environment Id '{$this->config->getEnvironmentId()}'");

            throw $e;
        }
    }
}
