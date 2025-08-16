<?php
declare(strict_types=1);
namespace Qase\PHPUnitReporter\Config;
use PHPUnit\Runner\Extension\ParameterCollection;

class PhpUnitConfigFactory
{
    public function create(?ParameterCollection $parameters = null): PhpUnitConfig
    {
        if ($parameters === null) {
            return new PhpUnitConfig();
        }

        $onlyReportTestsWithSuite = $parameters->has('onlyReportTestsWithSuite') && $this->castToBool($parameters->get('onlyReportTestsWithSuite'));
        $debug = $parameters->has('debug') && $this->castToBool($parameters->get('debug'));
        $formatTitleFromMethodName = $parameters->has('formatTitleFromMethodName') && $this->castToBool($parameters->get('formatTitleFromMethodName'));

        return new PhpUnitConfig(
            $debug,
            $onlyReportTestsWithSuite,
            $formatTitleFromMethodName,
        );
    }

    private function castToBool(string $value): bool
    {
        return match ($value) {
            'true', '1', 'yes' => true,
            default => false,
        };
    }
}