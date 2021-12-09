<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

class RunResult
{
    /**
     * @var string
     */
    private $projectCode;

    /**
     * @var int|null
     */
    private $runId;

    /**
     * @var bool
     */
    private $completeRunAfterSubmit;

    private $results = [];

    public function __construct(string $projectCode, ?int $runId, bool $completeRunAfterSubmit)
    {
        $this->projectCode = $projectCode;
        $this->runId = $runId;
        $this->completeRunAfterSubmit = $completeRunAfterSubmit;
    }

    public function getRunId(): ?int
    {
        return $this->runId;
    }

    public function getProjectCode(): string
    {
        return $this->projectCode;
    }

    public function getCompleteRunAfterSubmit(): bool
    {
        return $this->completeRunAfterSubmit;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function addResult(int $caseId, array $result)
    {
        $this->results[$caseId][] = $result;
    }
}
