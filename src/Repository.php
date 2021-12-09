<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use GuzzleHttp\Psr7\Request;
use Qase\Client\Api\CasesApi;
use Qase\Client\Api\ProjectsApi;
use Qase\Client\Api\ResultsApi;
use Qase\Client\Api\RunsApi;
use Qase\Client\Api\SuitesApi;
use Qase\Client\Configuration;
use Qase\Client\Model\SuiteCreate;
use Qase\Client\Model\TestCaseCreate;

class Repository
{
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
     * @var ResultsApi
     */
    private $resultsApi;

    /**
     * @var CasesApi
     */
    private $casesApi;

    /**
     * @var SuitesApi
     */
    private $suitesApi;

    /**
     * @var array
     */
    private $suitesFlat;

    /**
     * @var ConsoleLogger
     */
    private $logger;

    /**
     * @var array
     */
    private $existingCases = [];

    /**
     * @var array
     */
    private $existingCasesGroupedBySuite = [];

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct(string $projectCode, ConsoleLogger $logger)
    {
        $this->projectCode = $projectCode;
        $this->logger = $logger;
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

    public function init()
    {
        $this->client = new \GuzzleHttp\Client([
            'headers' => [
                'X-Platform' => sprintf('php=%s', phpversion()),
                'X-Client' => sprintf('qase-phpunit=%s', Reporter::VERSION),
            ],
        ]);

        $config = Configuration::getDefaultConfiguration()
            ->setHost(getenv('QASE_API_BASE_URL'))
            ->setApiKey('Token', getenv('QASE_API_TOKEN'));

        $this->runsApi = new RunsApi($this->client, $config);
        $this->projectsApi = new ProjectsApi($this->client, $config);
        $this->resultsApi = new ResultsApi($this->client, $config);
        $this->casesApi = new CasesApi($this->client, $config);
        $this->suitesApi = new SuitesApi($this->client, $config);

        $this->getCases();
        $this->getSuites();
    }

    public function getCases(): void
    {
        $cases = $this->getAllEntities($this->casesApi->getCasesRequest($this->projectCode));

        foreach ($cases as $case) {
            $this->existingCases[$case['id']] = $case['title'];
            $this->existingCasesGroupedBySuite[$case['suite_id'] ?: 0][$case['title']] = $case['id'];
        }
    }

    public function getSuites(): void
    {
        $suites = $this->getAllEntities($this->suitesApi->getSuitesRequest($this->projectCode));

        $suites = array_map(
            static function (array $suite) {
                return [
                    'id' => $suite['id'],
                    'parent_id' => $suite['parent_id'],
                    'title' => $suite['title'],
                ];
            },
            $suites
        );

        $suitesTree = $this->buildSuitesTree($suites);
        $this->suitesFlat = $this->flatArray($suitesTree);
    }

    private function getAllEntities(Request $request): array
    {
        $entities = [];

        $limit = 100;
        $offset = 0;

        do {
            $rawResponse = $this->client->send(
                $request,
                [
                    'query' => ['limit' => $limit, 'offset' => $offset,],
                ]
            );

            $response = \GuzzleHttp\json_decode((string)$rawResponse->getBody(), true);

            $entities = array_merge($entities, $response['result']['entities']);

            $offset += $limit;
        } while (count($response['result']['entities']) > 0 && count($entities) < $response['result']['total']);

        return $entities;
    }

    public function findOrCreateCase(string $suiteTitle, string $testTitle): int
    {
        $caseId = $this->findCaseByTitle($suiteTitle, $testTitle);

        if ($caseId) {
            return $caseId;
        }

        return $this->createCase($suiteTitle, $testTitle);
    }

    public function findCaseByTitle(string $suiteTitle, string $testTitle): ?int
    {
        if (!isset($this->suitesFlat[$suiteTitle], $this->existingCasesGroupedBySuite[$this->suitesFlat[$suiteTitle]][$testTitle])) {
            return null;
        }

        return $this->existingCasesGroupedBySuite[$this->suitesFlat[$suiteTitle]][$testTitle];
    }

    private function createCase(string $suiteTitle, string $testTitle): int
    {
        $suiteId = $this->findOrCreateSuite($suiteTitle);

        $case = $this->casesApi->createCase(
            $this->projectCode,
            new TestCaseCreate([
                'title' => $testTitle,
                'automation' => 2,
                'suiteId' => $suiteId,
            ])
        );

        $this->existingCasesGroupedBySuite[$suiteId][$testTitle] = $case->getResult()->getId();

        $this->logger->writeln("created new test case '$testTitle' with ID " . $case->getResult()->getId());

        return $case->getResult()->getId();
    }

    private function findOrCreateSuite(string $suiteTitle): int
    {
        if (isset($this->suitesFlat[$suiteTitle])) {
            return $this->suitesFlat[$suiteTitle];
        }

        $suitesToCreate = [];
        $partialSuitTitle = $suiteTitle;
        $suitePartsCount = substr_count($suiteTitle, '\\') + 1;

        for ($i = 1; $i <= $suitePartsCount; $i++) {
            if (isset($this->suitesFlat[$partialSuitTitle])) {
                break;
            }

            $dividerPosition = mb_strrpos($partialSuitTitle, '\\');

            if ($dividerPosition === false) {
                $suitesToCreate[] = [
                    'short' => $partialSuitTitle,
                    'full' => $partialSuitTitle,
                ];
                break;
            }

            $suitesToCreate[] = [
                'short' => ltrim(mb_substr($partialSuitTitle, $dividerPosition), '\\'),
                'full' => $partialSuitTitle,
            ];

            $partialSuitTitle = mb_substr($partialSuitTitle, 0, $dividerPosition);
        }

        $parentId = $this->suitesFlat[$partialSuitTitle] ?? null;
        foreach (array_reverse($suitesToCreate) as $suiteName) {
            $newSuite = $this->suitesApi->createSuite(
                $this->projectCode,
                new SuiteCreate([
                    'title' => $suiteName['short'],
                    'parentId' => $parentId,
                ])
            );

            $this->suitesFlat[$suiteName['full']] = $newSuite->getResult()->getId();
            $parentId = $newSuite->getResult()->getId();

            $this->logger->writeln("created new suite '{$suiteName['full']}' with ID " . $newSuite->getResult()->getId());
        }

        if (!isset($newSuite)) {
            throw new \RuntimeException("could not create a suite '$suiteTitle'");
        }

        return $newSuite->getResult()->getId();
    }

    public function caseIdExists(int $caseId): bool
    {
        return isset($this->existingCases[$caseId]);
    }

    public function flatArray(array $arr, string $prefix = ''): array
    {
        $flatten = [];

        foreach ($arr as $k => $v) {
            if (is_array($v) && array_keys($v) !== range(0, count($v) - 1)) {
                $flatten = array_merge($flatten, $this->flatArray($v, $prefix . (isset($v['title']) ? '\\' . $v['title'] : '')));
            } else {
                if ($k === 'id') {
                    $flatten[ltrim($prefix, '\\')] = $v;
                }
            }
        }

        return $flatten;
    }

    public function buildSuitesTree(array &$elements, ?int $parentId = null, $rootField = 'id'): array
    {
        $branch = [];

        foreach ($elements as &$element) {
            if ($element['parent_id'] === $parentId) {
                $children = self::buildSuitesTree($elements, $element['id'], $rootField);
                if ($children) {
                    $element['suites'] = $children;
                }
                $branch[$element[$rootField]] = $element;
                unset($element);
            }
        }

        return $branch;
    }
}
