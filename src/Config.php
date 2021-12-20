<?php

namespace Qase\PHPUnit;

class Config
{
    public const REQUIRED_PARAMS = [
        'QASE_PROJECT_CODE',
        'QASE_API_BASE_URL',
        'QASE_API_TOKEN',
    ];

    private ?string $projectCode;
    private ?string $baseUrl;
    private ?string $apiToken;
    private ?int $runId;
    private ?string $rootSuiteTitle;
    private bool $completeRunAfterSubmit;

    public function __construct()
    {
        $this->baseUrl = getenv('QASE_API_BASE_URL') ?? null;
        $this->apiToken = getenv('QASE_API_TOKEN') ?? null;
        $this->projectCode = getenv('QASE_PROJECT_CODE') ?? null;
        $this->rootSuiteTitle = getenv('QASE_ROOT_SUITE_TITLE') ?? null;

        $this->runId = getenv('QASE_RUN_ID') ? (int)getenv('QASE_RUN_ID') : null;
        $this->completeRunAfterSubmit = is_null($this->runId) || getenv('QASE_RUN_COMPLETE') === '1';
    }

    public function getProjectCode(): ?string
    {
        return $this->projectCode;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function getRunId(): ?int
    {
        return $this->runId;
    }

    public function getCompleteRunAfterSubmit(): bool
    {
        return $this->completeRunAfterSubmit;
    }

    public function getRootSuiteTitle(): ?string
    {
        return $this->rootSuiteTitle;
    }
}
