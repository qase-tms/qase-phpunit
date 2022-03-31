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
    private RunsApi $runsApi;
    private ProjectsApi $projectsApi;
    private ResultsApi $resultsApi;

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

    public function init(Config $config, array $headers, \GuzzleHttp\Client $client = null)
    {
        if($client === null) {
            $client = new \GuzzleHttp\Client([
                'headers' => $headers,
            ]);
        }

        $config = Configuration::getDefaultConfiguration()
            ->setHost($config->getBaseUrl())
            ->setApiKey('Token', $config->getApiToken());

        $this->runsApi = new RunsApi($client, $config);
        $this->projectsApi = new ProjectsApi($client, $config);
        $this->resultsApi = new ResultsApi($client, $config);
    }
}
