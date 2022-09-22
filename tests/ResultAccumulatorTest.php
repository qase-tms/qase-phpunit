<?php

namespace Tests;

use Qase\PhpClientUtils\RunResult;
use Qase\PHPUnit\ResultAccumulator;
use PHPUnit\Framework\TestCase;

class ResultAccumulatorTest extends TestCase
{

    /**
     * @dataProvider accumulateDataProvider
     * @throws \ReflectionException
     */
    public function testAutoCreateDefect(string $title, string $status, float $time, bool $expected)
    {
        $runResult = new RunResult('PRJ', null, true, null);

        $resultAccumulator = new ResultAccumulator($runResult, true);
        $resultAccumulator->accumulate($status, $title, $time);

        $this->assertEquals($runResult->getResults()[0]['defect'], $expected);
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
