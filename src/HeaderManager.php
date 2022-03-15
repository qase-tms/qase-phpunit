<?php

namespace Qase\PHPUnit;

class HeaderManager
{
    private const UNDEFINED_HEADER = 'undefined';

    private array $composerPackages = [];

    private function init(): void
    {
        $composerFilepath = __DIR__ . '/../../../../composer.lock';
        if (!file_exists($composerFilepath)) {
            return;
        }

        $composerLock = \json_decode(file_get_contents($composerFilepath), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return;
        }

        $packages = array_column($composerLock['packages'] ?? [], 'version', 'name');
        $packagesDev = array_column($composerLock['packages-dev'] ?? [], 'version', 'name');
        $this->composerPackages = array_merge($packages, $packagesDev);
    }

    public function getClientHeaders(): array
    {
        $this->init();

        $phpUnitVersion = $this->composerPackages['phpunit/phpunit'] ?? (class_exists('\PHPUnit\Runner\Version') ? \PHPUnit\Runner\Version::id() : self::UNDEFINED_HEADER);
        $apiClientVersion = $this->composerPackages['qase/api'] ?? self::UNDEFINED_HEADER;
        $reporterVersion = $this->composerPackages['qase/phpunit-reporter'] ?? self::UNDEFINED_HEADER;

        if (is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
            $composerOutput = shell_exec('composer -V');
            preg_match('/Composer version\s(?P<version>(.+))\s/U', $composerOutput, $composerMatches);
        }
        $composerVersion = $composerMatches['version'] ?? self::UNDEFINED_HEADER;

        return [
            'X-Platform' => http_build_query([
                'os' => php_uname('s'),
                'arch' => php_uname('m'),
                'lang' => 'php',
                'lang_version' => phpversion(),
                'package_manager' => 'composer',
                'package_manager_version' => $composerVersion,
            ], '', ';'),
            'X-Client' => http_build_query([
                'client_version' => $apiClientVersion,
                'reporter' => 'qase-phpunit',
                'reporter_version' => $reporterVersion,
                'framework' => 'phpunit',
                'framework_version' => $phpUnitVersion,
            ], '', ';'),
        ];
    }
}
