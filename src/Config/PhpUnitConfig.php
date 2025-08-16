<?php
declare(strict_types=1);
namespace Qase\PHPUnitReporter\Config;

class PhpUnitConfig
{
    public function __construct(
        public readonly bool $debug = false,
        public readonly bool $onlyReportTestsWithSuite = false,
        public readonly bool $formatTitleFromMethodName = false,
    )
    {
    }
}