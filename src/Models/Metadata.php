<?php

namespace Qase\PHPUnitReporter\Models;

class Metadata
{
    public ?string $title = null;
    public ?int $qaseId = null;
    public array $suites = [];
    public array $parameters = [];
    public array $fields = [];
}
