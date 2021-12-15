<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

class RunResult
{
    private string $projectCode;
    private ?int $runId;
    private bool $completeRunAfterSubmit;
    private array $results = [];

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

    public function addResult(array $result)
    {
        $this->results[] = $result;
    }
}
