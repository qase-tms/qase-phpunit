<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use Qase\Client\ApiException;
use Qase\Client\Model\ResultCreate;
use Qase\Client\Model\ResultCreateBulk;
use Qase\Client\Model\RunCreate;

class ResultHandler
{
    private const SUITE_TITLE_SEPARATOR = "\t";

    private ConsoleLogger $logger;
    private Repository $repo;

    public function __construct(Repository $repo, ConsoleLogger $logger)
    {
        $this->logger = $logger;
        $this->repo = $repo;
    }

    /**
     * @throws ApiException
     */
    public function handle(RunResult $runResult, string $rootSuiteTitle): void
    {
        $this->logger->writeln('', '');
        $this->logger->writeln('Results handling started');

        $bulkResults = $this->prepareBulkResults($runResult, $rootSuiteTitle);

        if ($bulkResults === []) {
            $this->logger->writeln('WARNING: did not find any tests to report in the Qase TMS');
            return;
        }

        $this->submit($runResult, $bulkResults);
    }

    private function prepareBulkResults(RunResult $runResult, string $rootSuiteTitle): array
    {
        $bulkResults = [];
        $addedForSending = [];

        foreach ($runResult->getResults() as $item) {
            $resultData = [
                'status' => $item['status'],
                'timeMs' => (int)($item['time'] * 1000.0),
                'stacktrace' => $item['stacktrace'],
                'comment' => "{$item['full_test_name']} in {$item['time']}s",
            ];

            list($namespace, $methodName) = $this->explodeFullTestName($item['full_test_name']);

            $caseTitle = $this->clearPrefix($methodName, ['test']);
            $suiteTitle = $rootSuiteTitle . self::SUITE_TITLE_SEPARATOR .
                str_replace('\\', self::SUITE_TITLE_SEPARATOR, $this->clearPrefix($namespace, ['Test\\', 'Tests\\']));

            $caseId = $this->getCaseIdFromAnnotation($namespace, $methodName);
            if ($caseId) {
                if (!$this->repo->caseIdExists($caseId)) {
                    $this->logger->writeln(
                        "{$namespace}::{$methodName} skipped due to @qaseId contains unknown Qase ID: {$caseId}",
                    );

                    continue;
                }

                $resultData['caseId'] = $caseId;
            } else {
                $resultData['case'] = [
                    'title' => $caseTitle,
                    'suite_title' => $suiteTitle,
                    'automation' => 2,
                ];

            }

            if (preg_match('/with data set "(.+)"|(#\d+)/U', $item['full_test_name'], $paramMatches, PREG_UNMATCHED_AS_NULL) === 1) {
                $resultData['param'] = ['phpunit' => $paramMatches[1] ?? $paramMatches[2]];
            }

            $bulkResults[] = new ResultCreate($resultData);
            $addedForSending[$suiteTitle][] = $caseTitle;
        }

        foreach ($addedForSending as $suiteTitle => $caseTitles) {
            foreach (array_unique($caseTitles) as $caseTitle) {
                $suiteTitle = str_replace(self::SUITE_TITLE_SEPARATOR, '\\', $suiteTitle);
                $this->logger->writeln("[added for sending]'{$suiteTitle}' '{$caseTitle}'");
            }
        }

        return $bulkResults;
    }

    /**
     * @throws ApiException
     */
    private function submit(RunResult $runResult, array $bulkResults): void
    {
        $runId = $runResult->getRunId() ?: $this->createRunId($runResult->getProjectCode());

        $this->logger->write("publishing results for run #{$runId}... ");

        $this->repo->getResultsApi()->createResultBulk(
            $runResult->getProjectCode(),
            $runId,
            new ResultCreateBulk(['results' => $bulkResults])
        );

        $this->logger->writeln('OK', '');

        if ($runResult->getCompleteRunAfterSubmit()) {
            $this->logger->write("completing run #{$runId}... ");

            $this->repo->getRunsApi()->completeRun($runResult->getProjectCode(), $runId);

            $this->logger->writeln('OK', '');
        }
    }

    /**
     * @throws ApiException
     */
    private function createRunId($projectCode): int
    {
        $runName = 'Automated run ' . date('Y-m-d H:i:s');
        $runBody = new RunCreate([
            "title" => $runName,
            'isAutotest' => true,
        ]);

        $this->logger->write("creating run '{$runName}'... ");

        $response = $this->repo->getRunsApi()->createRun($projectCode, $runBody);

        $this->logger->writeln('OK', '');

        return $response->getResult()->getId();
    }

    private function getCaseIdFromAnnotation(string $namespace, string $methodName): ?int
    {
        $reflection = new \ReflectionMethod($namespace, $methodName);

        $docComment = $reflection->getDocComment();
        if (!$docComment || !preg_match_all('/\@qaseId?\s+(?P<caseId>.*?)\s*\r?$/m', $docComment, $qaseIdMatches)) {
            return null;
        }

        if (!($qaseIdMatches['caseId'][0] ?? false)) {
            return null;
        }

        return (int)$qaseIdMatches['caseId'][0] ?: null;
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

    private function explodeFullTestName($fullTestName): array
    {
        if (!preg_match_all('/(?P<namespace>.+)::(?P<methodName>\w+)/', $fullTestName, $testNameMatches)) {
            $this->logger->writeln("WARNING: Could not parse test name '{$fullTestName}'");
            throw new \RuntimeException('Could not parse test name');
        }

        return [$testNameMatches['namespace'][0], $testNameMatches['methodName'][0]];
    }
}
