<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use Qase\Client\ApiException;
use Qase\Client\Model\Response;
use Qase\Client\Model\ResultCreateBulk;
use Qase\Client\Model\RunCreate;

class ResultHandler
{

    private ConsoleLogger $logger;
    private Repository $repo;
    private ResultsConverter $resultsConverter;

    public function __construct(Repository $repo, ResultsConverter $resultsConverter, ConsoleLogger $logger)
    {
        $this->logger = $logger;
        $this->repo = $repo;
        $this->resultsConverter = $resultsConverter;
    }

    /**
     * @throws ApiException
     */
    public function handle(RunResult $runResult, string $rootSuiteTitle): ?Response
    {
        $this->logger->writeln('', '');
        $this->logger->writeln('Results handling started');

        $bulkResults = $this->resultsConverter->prepareBulkResults($runResult, $rootSuiteTitle);

        if ($bulkResults === []) {
            $this->logger->writeln('WARNING: did not find any tests to report in the Qase TMS');
            return null;
        }

        return $this->submit($runResult, $bulkResults);
    }

    /**
     * @throws ApiException
     */
    private function submit(RunResult $runResult, array $bulkResults): Response
    {
        $runId = $runResult->getRunId() ?: $this->createRunId($runResult->getProjectCode());

        $this->logger->write("publishing results for run #{$runId}... ");

        $response = $this->repo->getResultsApi()->createResultBulk(
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

        return $response;
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

        if ($response->getResult() === null) {
            throw new \RuntimeException('Could not create run');
        }

        $this->logger->writeln('OK', '');

        return $response->getResult()->getId();
    }
}
