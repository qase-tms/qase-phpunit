<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSkippedTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Util\Exception;
use Qase\Client\ApiException;

class Reporter implements AfterSuccessfulTestHook, AfterSkippedTestHook, AfterTestFailureHook, AfterTestErrorHook, AfterLastTestHook, BeforeFirstTestHook
{
    public const VERSION = 'alpha';

    private const ROOT_SUITE_TITLE = 'PHPUnit tests';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    private const FAILED = 'failed';

    /**
     * @var RunResult
     */
    private $runResult;

    /**
     * @var Repository
     */
    private $repo;

    /**
     * @var ResultHandler
     */
    private $resultHandler;

    /**
     * @var ConsoleLogger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    public function __construct()
    {
        $this->logger = new ConsoleLogger();
        $this->config = new Config();

        $this->validateConfig();

        $this->runResult = new RunResult(
            $this->config->getProjectCode(),
            $this->config->getRunId(),
            $this->config->getCompleteRunAfterSubmit(),
        );

        $this->repo = new Repository($this->runResult->getProjectCode(), $this->logger);
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

    public function executeBeforeFirstTest(): void
    {
        $this->repo->init(
            $this->config,
            [
                'X-Platform' => sprintf('php=%s', phpversion()),
                'X-Client' => sprintf('qase-phpunit=%s', self::VERSION),
            ]
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
        $caseId = $this->getCaseId($test);

        if (!$caseId) {
            return;
        }

        if (preg_match('/with data set "(.+)"|(#\d+)/U', $test, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
            $params = $matches[1] ?? $matches[2];
        }

        $this->runResult->addResult(
            $caseId,
            [
                'status' => $status,
                'time' => $time,
                'testName' => $test,
                'message' => $message,
                'params' => $params ?? null,
            ]
        );
    }

    private function getCaseId(string $testName): ?int
    {
        if (!preg_match_all('/(?P<namespace>.+)::(?P<methodName>\w+)/', $testName, $testNameMatches)) {
            $this->logger->writeln("WARNING: Could not parse test name '{$testName}'");
            return null;
        }

        $namespace = $testNameMatches['namespace'][0];
        $methodName = $testNameMatches['methodName'][0];

        $caseTitle = $this->clearPrefix($methodName, ['test']);
        $suiteTitle = self::ROOT_SUITE_TITLE . '\\' . $this->clearPrefix($namespace, ['Test\\', 'Tests\\']);

        try {
            $reflection = new \ReflectionMethod($namespace, $methodName);
        } catch (\ReflectionException $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }

        $docComment = $reflection->getDocComment();
        if (!$docComment || !preg_match_all('/\@qaseId?[ \t]+(?P<caseId>.*?)[ \t]*\r?$/m', $docComment, $qaseIdMatches)) {
            return $this->repo->findOrCreateCase($suiteTitle, $caseTitle);
        }

        if (!($qaseIdMatches['caseId'][0] ?? false)) {
            return $this->repo->findOrCreateCase($suiteTitle, $caseTitle);
        }

        $caseId = (int)$qaseIdMatches['caseId'][0];

        if (!$this->repo->caseIdExists($caseId)) {
            $this->logger->writeln(sprintf(
                "%s::%s skipped due to @qaseId contains unknown Qase ID: %s",
                $namespace,
                $methodName,
                $caseId
            ));

            return null;
        }

        return $caseId;
    }

    public function executeAfterLastTest(): void
    {
        try {
            $this->resultHandler->handle(
                $this->runResult
            );
        } catch (\Exception $e) {
            $this->logger->writeln('An exception occurred');
            $this->logger->writeln($e->getMessage());

            return;
        }
    }

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

    /**
     * @param string $title
     * @param array[string] $prefixes
     * @return string
     */
    private function clearPrefix(string $title, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            $prefixLength = mb_strlen($prefix);
            if (strncmp($title, $prefix, $prefixLength) === 0) {
                return mb_substr($title, $prefixLength);
            }
        }

        return $title;
    }
}
