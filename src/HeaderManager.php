<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

class HeaderManager extends \Qase\PhpClientUtils\HeaderManager
{
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
                'php' => phpversion(),
                'composer' => $composerVersion,
            ], '', ';'),
            'X-Client' => http_build_query([
                'qaseapi' => $apiClientVersion,
                'qase-phpunit' => $reporterVersion,
                'phpunit' => $phpUnitVersion,
            ], '', ';'),
        ];
    }
}
