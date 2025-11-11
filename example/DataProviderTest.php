<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Parameter;
use Qase\PHPUnitReporter\Attributes\Title;

class DataProviderTest extends TestCase
{
    /**
     * Return a list of versions to test
     */
    public static function getProviderData(): array
    {
        return [
            'v1' => ['version', 'v1'],
            'v2' => ['version', 'v2'],
            'v3' => ['version', 'v3'],
        ];
    }

    #[
        DataProvider('getProviderData'),
        Parameter('version', ''),
        Title("Test version")
    ]
    public function testVersion(string $paramName, string $version): void
    {
        $this->assertStringContainsString('v', $version, "Version includes v");
    }

    /**
     * Simple data provider with indexed array
     */
    public static function getSimpleData(): array
    {
        return [
            ['value1'],
            ['value2'],
            ['value3'],
        ];
    }

    #[
        DataProvider('getSimpleData'),
        Title("Test simple data provider")
    ]
    public function testSimple(string $value): void
    {
        $this->assertNotEmpty($value);
    }

    /**
     * Data provider with associative array
     */
    public static function getAssociativeData(): array
    {
        return [
            ['browser' => 'chrome', 'version' => '120'],
            ['browser' => 'firefox', 'version' => '121'],
        ];
    }

    #[
        DataProvider('getAssociativeData'),
        Title("Test associative data provider")
    ]
    public function testAssociative(array $data): void
    {
        $this->assertArrayHasKey('browser', $data);
        $this->assertArrayHasKey('version', $data);
    }
}

