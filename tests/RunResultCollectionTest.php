<?php

namespace Tests;

use Qase\PhpClientUtils\Config;
use Qase\PhpClientUtils\RunResult;
use Qase\PHPUnit\RunResultCollection;
use PHPUnit\Framework\TestCase;

class RunResultCollectionTest extends TestCase
{

    /**
     * @dataProvider autoCreateDefectDataProvider
     */
    public function testAutoCreateDefect(string $title, string $status, float $time, bool $expected)
    {
        $runResult = $this->createMock(RunResult::class);
        $runResult->expects($this->once())
            ->method('addResult')
            ->with(
                $this->callback(function ($result) use ($expected) {
                    return isset($result['defect']) && $result['defect'] === $expected;
                })
            );

        $runResultCollection = $this->createRunResultCollection($runResult);
        $runResultCollection->add($status, $title, $time);
    }

    public function autoCreateDefectDataProvider(): array
    {
        return [
            ['Test (Qase ID: 1)', 'failed', 1, true],
            ['Test (Qase ID: 2)', 'passed', 1, false],
            ['Test (Qase ID: 3)', 'skipped', 1, false],
            ['Test (Qase ID: 4)', 'disabled', 1, false],
            ['Test (Qase ID: 5)', 'pending', 1, false],
        ];
    }

    public function testGettingRunResultFromCollection()
    {
        $runResultCollection = $this->createRunResultCollection();
        $this->assertInstanceOf(RunResult::class, $runResultCollection->get());
    }

    public function testResultCollectionIsEmptyWhenReportingIsDisabled()
    {
        $runResult = null;
        $isReportingEnabled = false;
        $runResultCollection = $this->createRunResultCollection($runResult, $isReportingEnabled);
        $runResultCollection->add('failed', 'Test 6', 1, 'Testing message');

        $runResult = $runResultCollection->get();

        $this->assertEmpty($runResult->getResults());
    }

    public function testAddingResults()
    {
        // Arrange
        $stackTraceMessage = 'Stack trace text';
        $expectedResult = [
            [
                'status' => 'failed',
                'time' => 1.0,
                'full_test_name' => 'Unit::methodName',
                'stacktrace' => $stackTraceMessage,
                'defect' => true,
            ],
            [
                'status' => 'passed',
                'time' => 0.375,
                'full_test_name' => 'Unit::methodName',
                'stacktrace' => null,
                'defect' => false,
            ],
        ];

        // Act: Initialize empty Collection
        $runResultCollection = $this->createRunResultCollection();
        // Assert: Insure results are empty
        $runResult = $runResultCollection->get();
        $this->assertEmpty($runResult->getResults());

        // Act: Add run results to the collection
        $runResultCollection->add('failed', 'Unit::methodName', 1, $stackTraceMessage);
        $runResultCollection->add('passed', 'Unit::methodName', 0.375);
        // Assert: Check collection results
        $runResult = $runResultCollection->get();
        $this->assertSame($runResult->getResults(), $expectedResult);
    }

    private function createRunResultCollection(
        ?RunResult $runResult = null,
        bool $isReportingEnabled = true
    ): RunResultCollection
    {
        $runResult = $runResult ?: new RunResult($this->createStub(Config::class));

        return new RunResultCollection($runResult, $isReportingEnabled);
    }
}
