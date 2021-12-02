<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSkippedTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Util\Exception;
use Qase\Client\Api\CasesApi;
use Qase\Client\Api\ProjectsApi;
use Qase\Client\Api\ResultsApi;
use Qase\Client\Api\RunsApi;
use Qase\Client\ApiException;
use Qase\Client\Configuration;
use Qase\Client\Model\ResultCreate;
use Qase\Client\Model\ResultCreateBulk;
use Qase\Client\Model\RunCreate;

class Reporter implements AfterSuccessfulTestHook, AfterSkippedTestHook, AfterTestFailureHook, AfterLastTestHook, BeforeFirstTestHook
{
    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    private const FAILED = 'failed';

    /**
     * @var string
     */
    private $projectCode;

    /**
     * @var RunsApi
     */
    private $runsApi;

    /**
     * @var ProjectsApi
     */
    private $projectsApi;

    /**
     * @var string
     */
    private $runName;

    /**
     * @var int|null
     */
    private $runId;

    /**
     * @var ResultsApi
     */
    private $resultsApi;

    /**
     * @var array
     */
    private $existingCasesById = [];

    /**
     * @var array
     */
    private $resultsByCase = [];

    /**
     * @var CasesApi
     */
    private $casesApi;

    /**
     * @var Configuration
     */
    private $config;

    public function __construct() {
        foreach (['QASE_PROJECT_CODE', 'QASE_API_BASE_URL', 'QASE_API_TOKEN',] as $parameter) {
            if (!getenv($parameter)) {
                fwrite(STDERR, $parameter . ' environment variable is not set. Reporting to qase.io is disabled.' . PHP_EOL);
                return;
            }
        }

        $this->projectCode = getenv('QASE_PROJECT_CODE');
        $this->runName = 'Automated test run ' . date('Y-m-d H:i:s');

        $client = new \GuzzleHttp\Client();
        $this->config = Configuration::getDefaultConfiguration()
            ->setHost(getenv('QASE_API_BASE_URL'))
            ->setApiKey('Token', getenv('QASE_API_TOKEN'));

        $this->runsApi = new RunsApi($client, $this->config);
        $this->projectsApi = new ProjectsApi($client, $this->config);
        $this->resultsApi = new ResultsApi($client, $this->config);
        $this->casesApi = new CasesApi($client, $this->config);
    }

    public function executeBeforeFirstTest(): void {
        $this->getCases();
    }

    public function executeAfterSkippedTest(string $test, string $message, float $time): void {
        $this->accumulateTestResult(self::SKIPPED, $test, $time, $message);

    }

    public function executeAfterSuccessfulTest(string $test, float $time): void {
        $this->accumulateTestResult(self::PASSED, $test, $time);
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void {
        $this->accumulateTestResult(self::FAILED, $test, $time, $message);
    }

    private function accumulateTestResult(string $status, string $test, float $time, string $message = NULL): void {
        $caseId = $this->getCaseIds($test);

        if (!$caseId) {
            return;
        }

        $result = [
            'status' => $status,
            'time' => $time,
            'testName' => $test,
            'message' => $message,
        ];

        if (!isset($this->resultsByCase[$caseId])) {
            $this->resultsByCase[$caseId] = [];
        }

        $this->resultsByCase[$caseId][] = $result;
    }

    private function getCaseIds(string $testName): ?int {
        if (!preg_match_all('/(?P<namespace>.+)::(?P<methodName>\w+)/', $testName, $testNameMatches)) {
            $this->writeln("WARNING: Could not parse test name '{$testName}'");
            return null;
        }

        $namespace = $testNameMatches['namespace'][0];
        $methodName = $testNameMatches['methodName'][0];

        try {
            $reflection = new \ReflectionMethod($namespace, $methodName);
        } catch (\ReflectionException $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }

        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return null;
        }

        if (!preg_match_all('/\@qaseId?[ \t]+(?P<caseIds>.*?)[ \t]*\r?$/m', $docComment, $qaseIdMatches)) {
            return null;
        }

        if ($qaseIdMatches['caseIds'][0] ?? false) {
            $caseId = (int)$qaseIdMatches['caseIds'][0];

            return isset($this->existingCasesById[$caseId]) ? $caseId : null;
        }

        return null;
    }

    public function executeAfterLastTest(): void {
        $this->writeln('', '');

        if (empty($this->resultsByCase)) {
            $this->writeln('WARNING: did not find any tests to report in Qase');
            return;
        }

        try {
            $this->write("checking if project '$this->projectCode' exists... ");
            $this->projectsApi->getProject($this->projectCode);
            $this->writeln('OK', '');
        } catch (ApiException $e) {
            fwrite(STDERR, "qase: can't find project $this->projectCode");
            return;
        }

        try {
            $this->write("creating run '$this->runName'... ");

            $response = $this->runsApi->createRun(
                $this->projectCode,
                new RunCreate([
                    "title" => $this->runName,
                    'isAutotest' => true,
                ])
            );

            $this->runId = $response->getResult()->getId();

            $this->writeln('OK', '');
        } catch (ApiException $e) {
            fwrite(STDERR, 'qase: could not create run ' . $this->runName);
            fwrite(STDERR, $e->getMessage());
            return;
        }

        $bulkResults = [];
        foreach ($this->resultsByCase as $caseId => $resultsForCase) {
            $status = $this->getCaseStatus($resultsForCase);

            $testNames = array_map(
                static function ($r) {
                    return "{$r['testName']} in {$r['time']}s";
                },
                $resultsForCase,
            );

            $totalTime = array_reduce(
                $resultsForCase,
                static function ($time, $r) {
                    return $time + $r['time'];
                },
                0.0,
            );

            $failedTests = array_filter(
                $resultsForCase,
                static function ($r) {
                    return $r['status'] === self::FAILED;
                },
            );

            $messagesFromFailedTests = array_map(
                static function ($r) {
                    return $r['message'];
                },
                $failedTests,
            );

            $bulkResults[] = new ResultCreate([
                'caseId' => $caseId,
                'status' => $status,
                'timeMs' => (int)($totalTime * 1000.0),
                'stacktrace' => $status === self::FAILED ? join("\n", $messagesFromFailedTests) : null,
                'comment' => join("\n", $testNames),
                'param' => ['da' => 'net'],
            ]);
        }

        $this->write("publishing results for run #$this->runId... ");

        $this->resultsApi->createResultBulk(
            $this->projectCode,
            $this->runId,
            new ResultCreateBulk(['results' => $bulkResults])
        );

        $this->writeln('OK', '');

        $this->write("completing run #{$this->runId}... ");

        $this->runsApi->completeRun($this->projectCode, $this->runId);

        $this->writeln('OK', '');
    }

    private function getCaseStatus(array $results): string {
        $allStatuses = array_column($results, 'status');

        if (in_array(self::FAILED, $allStatuses, true)) {
            return self::FAILED;
        }

        if (in_array(self::PASSED, $allStatuses, true)) {
            return self::PASSED;
        }

        return self::SKIPPED;
    }

    private function write(string $message, string $prefix = '[Qase reporter]'): void {
        if ($prefix) {
            $message = sprintf('%s %s', $prefix, $message);
        }

        print $message;
    }

    private function writeln(string $message, string $prefix = '[Qase reporter]'): void {
        $this->write($message, $prefix);
        print PHP_EOL;
    }

    private function getCases(): void {
        $limit = 100;
        $offset = 0;

        do {
            $casesResponse = (new \GuzzleHttp\Client())->send(
                $this->casesApi->getCasesRequest($this->projectCode),
                [
                    'base_uri' => $this->config->getHost(),
                    'query' => ['limit' => $limit, 'offset' => $offset,],
                    'headers' => ['Token' => $this->config->getApiKey('Token'),],
                ]
            );

            $cases = \GuzzleHttp\json_decode((string)$casesResponse->getBody(), true);

            foreach ($cases['result']['entities'] as $caseEntity) {
                $this->existingCasesById[$caseEntity['id']] = $caseEntity;
            }

            $offset += $limit;
        } while (count($cases['result']['entities']) > 0 && count($this->existingCasesById) < $cases['result']['total']);
    }
}
