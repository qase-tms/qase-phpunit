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

class Reporter implements AfterSuccessfulTestHook, AfterSkippedTestHook, AfterTestFailureHook, AfterTestErrorHook, AfterLastTestHook, BeforeFirstTestHook
{
    private const ROOT_SUITE_TITLE = 'PHPUnit tests';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    private const FAILED = 'failed';

    private RunResult $runResult;
    private Repository $repo;
    private ResultHandler $resultHandler;
    private ConsoleLogger $logger;
    private Config $config;
    private HeaderManager $headerManager;

    public function __construct()
    {
        $this->logger = new ConsoleLogger();
        $this->config = new Config();
        $this->headerManager = new HeaderManager();

        $this->validateConfig();

        $this->runResult = new RunResult(
            $this->config->getProjectCode(),
            $this->config->getRunId(),
            $this->config->getCompleteRunAfterSubmit(),
        );

        $this->repo = new Repository($this->runResult->getProjectCode());
        $this->resultHandler = new ResultHandler($this->repo, $this->logger);
    }

    private function validateConfig(): void
    {
        if (!$this->config->getBaseUrl() || !$this->config->getApiToken() || !$this->config->getProjectCode()) {
            throw new \LogicException(sprintf(
                'The Qase PHPUnit reporter needs the following environment variables to be set: %s.',
                implode(',', Config::REQUIRED_PARAMS)
            ));
        }
    }

    /**
     * @throws ApiException
     */
    public function executeBeforeFirstTest(): void
    {
        $this->repo->init(
            $this->config,
            $this->headerManager->getClientHeaders()
        );

        $this->validateProjectCode();
    }

    public function executeAfterSkippedTest(string $test, string $message, float $time): void
    {
        $this->accumulateTestResult(self::SKIPPED, $test, $time, $message);
    }

    public function executeAfterSuccessfulTest(string $test, float $time): void
    {
        $this->accumulateTestResult(self::PASSED, $test, $time);
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        $this->accumulateTestResult(self::FAILED, $test, $time, $message);
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        $this->accumulateTestResult(self::FAILED, $test, $time, $message);
    }

    private function accumulateTestResult(string $status, string $test, float $time, string $message = null): void
    {
        $this->runResult->addResult([
            'status' => $status,
            'time' => $time,
            'full_test_name' => $test,
            'stacktrace' => $status === self::FAILED ? $message : null,
        ]);
    }


    public function executeAfterLastTest(): void
    {
        try {
            $this->resultHandler->handle(
                $this->runResult,
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
            $this->logger->write("checking if project '{$this->runResult->getProjectCode()}' exists... ");

            $this->repo->getProjectsApi()->getProject($this->runResult->getProjectCode());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find project '{$this->runResult->getProjectCode()}'");

            throw $e;
        }
    }
}
