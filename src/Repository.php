<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use GuzzleHttp\Psr7\Request;
use Qase\Client\Api\CasesApi;
use Qase\Client\Api\ProjectsApi;
use Qase\Client\Api\ResultsApi;
use Qase\Client\Api\RunsApi;
use Qase\Client\Configuration;

class Repository
{
    private string $projectCode;
    private RunsApi $runsApi;
    private ProjectsApi $projectsApi;
    private ResultsApi $resultsApi;
    private CasesApi $casesApi;
    private array $existingCases = [];
    private array $existingCasesGroupedBySuite = [];
    private \GuzzleHttp\Client $client;

    public function __construct(string $projectCode)
    {
        $this->projectCode = $projectCode;
    }

    /**
     * @return RunsApi
     */
    public function getRunsApi(): RunsApi
    {
        return $this->runsApi;
    }

    /**
     * @return ProjectsApi
     */
    public function getProjectsApi(): ProjectsApi
    {
        return $this->projectsApi;
    }

    /**
     * @return ResultsApi
     */
    public function getResultsApi(): ResultsApi
    {
        return $this->resultsApi;
    }

    public function init(Config $config, array $headers)
    {
        $this->client = new \GuzzleHttp\Client([
            'headers' => $headers,
        ]);

        $config = Configuration::getDefaultConfiguration()
            ->setHost($config->getBaseUrl())
            ->setApiKey('Token', $config->getApiToken());

        $this->runsApi = new RunsApi($this->client, $config);
        $this->projectsApi = new ProjectsApi($this->client, $config);
        $this->resultsApi = new ResultsApi($this->client, $config);
        $this->casesApi = new CasesApi($this->client, $config);

        $this->getCases();
    }

    private function getCases(): void
    {
        $cases = $this->getAllEntities($this->casesApi->getCasesRequest($this->projectCode));

        foreach ($cases as $case) {
            $this->existingCases[$case['id']] = $case['title'];
            $this->existingCasesGroupedBySuite[$case['suite_id'] ?: 0][$case['title']] = $case['id'];
        }
    }

    private function getAllEntities(Request $request): array
    {
        $limit = 100;
        $offset = 0;

        $response = $this->getEntities($request, $limit, $offset);

        $entities = $response['entities'];
        $total = $response['total'];

        $numOfPages = (int)ceil($total / $limit);

        for ($i = 1; $i < $numOfPages; $i++) {
            $response = $this->getEntities($request, $limit, $limit * $i);
            $responseEntities = $response['entities'];

            $entities = array_merge($entities, $responseEntities);

            if (count($responseEntities) === 0 || count($entities) >= $total) {
                break;
            }
        }

        return $entities;
    }

    private function getEntities(Request $request, int $limit, int $offset)
    {
        $rawResponse = $this->client->send(
            $request,
            [
                'query' => ['limit' => $limit, 'offset' => $offset,],
            ]
        );

        $response = \GuzzleHttp\json_decode((string)$rawResponse->getBody(), true);

        return $response['result'];
    }

    public function caseIdExists(int $caseId): bool
    {
        return isset($this->existingCases[$caseId]);
    }
}
