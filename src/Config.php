<?php

namespace Qase\PHPUnit;

class Config
{
    public const REQUIRED_PARAMS = [
        'QASE_PROJECT_CODE',
        'QASE_API_BASE_URL',
        'QASE_API_TOKEN',
    ];

    /**
     * @var string|null
     */
    private $projectCode;

    /**
     * @var string|null
     */
    private $baseUrl;

    /**
     * @var string|null
     */
    private $apiToken;

    /**
     * @var int|null
     */
    private $runId;

    /**
     * @var bool
     */
    private $completeRunAfterSubmit;

    public function __construct()
    {
        $this->baseUrl = getenv('QASE_API_BASE_URL') ?? null;
        $this->apiToken = getenv('QASE_API_TOKEN') ?? null;
        $this->projectCode = getenv('QASE_PROJECT_CODE') ?? null;

        $this->runId = getenv('QASE_RUN_ID') ? (int)getenv('QASE_RUN_ID') : null;
        $this->completeRunAfterSubmit = is_null($this->runId) || getenv('QASE_COMPLETE_RUN_AFTER_SUBMIT') === '1';
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
}
