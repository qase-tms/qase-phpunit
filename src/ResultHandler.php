<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use Qase\Client\Model\ResultCreate;
use Qase\Client\Model\ResultCreateBulk;
use Qase\Client\Model\RunCreate;

class ResultHandler
{
    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    private const FAILED = 'failed';

    /**
     * @var ConsoleLogger
     */
    private $logger;

    /**
     * @var Repository
     */
    private $repo;

    public function __construct(Repository $repo, ConsoleLogger $logger)
    {
        $this->logger = $logger;
        $this->repo = $repo;
    }

    public function handle(RunResult $runResult): void
    {
        $this->logger->writeln('', '');
        $this->logger->writeln('Results handling started');

        if (empty($runResult->getResults())) {
            $this->logger->writeln('WARNING: did not find any tests to report in the Qase TMS');
            return;
        }

        $runId =
            $runResult->getRunId() !== null ?
                $runResult->getRunId() :
                $this->createRunId(
                    $runResult->getProjectCode()
                );

        $bulkResults = [];
        foreach ($runResult->getResults() as $caseId => $resultsForCase) {
            foreach ($resultsForCase as $item) {
                $resultData = [
                    'caseId' => $caseId,
                    'status' => $item['status'],
                    'timeMs' => (int)($item['time'] * 1000.0),
                    'stacktrace' => $item['status'] === self::FAILED ? $item['message'] : null,
                    'comment' => "{$item['testName']} in {$item['time']}s",
                ];

                if ($item['params']) {
                    $resultData['param'] = ['phpunit' => $item['params']];
                }

                $bulkResults[] = new ResultCreate($resultData);
            }
        }

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
}
