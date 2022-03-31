<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Qase\Client\Api\ResultsApi;
use Qase\Client\Api\RunsApi;
use Qase\Client\Model\IdResponse;
use Qase\Client\Model\IdResponseAllOfResult;
use Qase\PHPUnit\ConsoleLogger;
use Qase\PHPUnit\Repository;
use Qase\PHPUnit\ResultHandler;
use Qase\PHPUnit\ResultsConverter;
use Qase\PHPUnit\RunResult;

class ResultHandlerTest extends TestCase
{
    /**
     * @dataProvider runIdDataProvider
     */
    public function testSuccessfulHandling(?int $runId, string $testName): void
    {
        $runResult = new RunResult('PRJ', $runId, true);
        $runResult->addResult([
            'status' => 'passed',
            'time' => 123,
            'stacktrace' => '',
            'full_test_name' => SomeTest::class . '::' . $testName,
        ]);

        $response = $this->runResultsHandler($runResult);

        $this->assertTrue($response->getStatus());
    }

    public function testHandlingWithNoResults(): void
    {
        $runResult = new RunResult('PRJ', 1, true);

        $response = $this->runResultsHandler($runResult);

        $this->assertNull($response);
    }

    public function runIdDataProvider(): array
    {
        return [
            [1, 'testImportantStuff'],
            [10, 'testAwesomeStuff'],
            [null, 'testImportantStuff']
        ];
    }

    private function createRepository(): Repository
    {
        $runsApi = $this->getMockBuilder(RunsApi::class)->getMock();
        $runsApi->method('createRun')->willReturn(
            new IdResponse([
                'status' => true,
                'result' => new IdResponseAllOfResult(['id' => 88,]),
            ])
        );

        $client = $this->getMockBuilder(Client::class)->getMock();
        $client->method('send')->willReturn(
            new Response(200, [], json_encode(['status' => true]))
        );

        $repository = $this->getMockBuilder(Repository::class)->getMock();
        $repository->method('getResultsApi')->willReturn(
            new ResultsApi($client)
        );
        $repository->method('getRunsApi')->willReturn(
            $runsApi
        );

        return $repository;
    }

    private function createLogger(): ConsoleLogger
    {
        return $this->getMockBuilder(ConsoleLogger::class)->getMock();
    }

    private function createConverter(): ResultsConverter
    {
        return new ResultsConverter($this->createLogger());
    }

    private function runResultsHandler(RunResult $runResult): ?\Qase\Client\Model\Response
    {
        $handler = new ResultHandler(
            $this->createRepository(),
            $this->createConverter(),
            $this->createLogger()
        );

        return $handler->handle($runResult, '');
    }
}
