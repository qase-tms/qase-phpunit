<?php

namespace Tests;

use Qase\PhpClientUtils\RunResult;
use Qase\PHPUnit\ResultAccumulator;
use PHPUnit\Framework\TestCase;

class ResultAccumulatorTest extends TestCase
{

    /**
     * @dataProvider accumulateDataProvider
     */
    public function testAutoCreateDefect(string $title, string $status, float $time, bool $expected)
    {
        $runResult = $this->getMockBuilder(RunResult::class)
            ->setConstructorArgs(['PRJ', null, true])
            ->getMock();

        $runResult->expects($this->once())
            ->method('addResult')
            ->with(
                $this->callback(function ($o) use ($expected) {
                    return isset($o['defect']) && $o['defect'] === $expected;
                })
            );

        $resultAccumulator = new ResultAccumulator($runResult, true);
        $resultAccumulator->accumulate($status, $title, $time);
    }

    function accumulateDataProvider(): array
    {
        return [
            ['Test (Qase ID: 1)', 'failed', 1, true],
            ['Test (Qase ID: 2)', 'passed', 1, false],
            ['Test (Qase ID: 3)', 'skipped', 1, false],
            ['Test (Qase ID: 4)', 'disabled', 1, false],
            ['Test (Qase ID: 5)', 'pending', 1, false],
        ];
    }

}
