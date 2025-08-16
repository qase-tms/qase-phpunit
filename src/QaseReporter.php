<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

use PHPUnit\Event\Code\TestMethod;
use Qase\PhpCommons\Interfaces\ReporterInterface;
use Qase\PhpCommons\Models\Attachment;
use Qase\PhpCommons\Models\Result;
use Qase\PhpCommons\Utils\Signature;
use Qase\PHPUnitReporter\Attributes\AttributeParserInterface;
use Qase\PHPUnitReporter\Config\PhpUnitConfig;

class QaseReporter implements QaseReporterInterface
{
    protected static QaseReporter $instance;
    protected array $testResults = [];
    protected ?string $currentKey = null;

    protected function __construct(
        protected readonly AttributeParserInterface $attributeParser,
        protected readonly ReporterInterface $reporter,
        protected readonly PhpUnitConfig $config,
    )
    {
    }

    public static function getInstance(
        AttributeParserInterface $attributeParser,
        ReporterInterface $reporter,
        PhpUnitConfig $config,
    ): QaseReporter
    {
        if (!isset(self::$instance)) {
            self::$instance = new QaseReporter($attributeParser, $reporter, $config);
        }
        return self::$instance;
    }

    public static function getInstanceWithoutInit(): ?QaseReporter
    {
        return self::$instance;
    }

    public function startTestRun(): void
    {
        $this->reporter->startRun();
    }

    public function completeTestRun(): void
    {
        $this->reporter->completeRun();
    }

    public function startTest(TestMethod $test): void
    {
        $key = $this->getTestKey($test);

        $metadata = $this->attributeParser->parseAttribute($test->className(), $test->methodName());

        $testResult = new Result();

        if (!empty($metadata->qaseIds)) {
            $testResult->testOpsIds = $metadata->qaseIds;
        }

        if (empty($metadata->suites)) {
            if ($this->config->onlyReportTestsWithSuite){
                return;
            }
            $suites = explode('\\', $test->className());
            foreach ($suites as $suite) {
                $testResult->relations->addSuite($suite);
            }
        } else {
            foreach ($metadata->suites as $suite) {
                $testResult->relations->addSuite($suite);
            }
        }

        $testResult->fields = $metadata->fields;
        $testResult->params = $metadata->parameters;
        $testResult->signature = $this->createSignature($test, $metadata->qaseIds, $metadata->suites, $metadata->parameters);
        $testResult->execution->setThread($this->getThread());

        $testResult->title = $metadata->title ?? $test->methodName();

        $this->currentKey = $key;
        $this->testResults[$key] = $testResult;
    }

    public function updateStatus(TestMethod $test, string $status, ?string $message = null, ?string $stackTrace = null): void
    {
        $key = $this->getTestKey($test);

        if (!isset($this->testResults[$key])) {
            if ($this->config->onlyReportTestsWithSuite){
                return;
            }
            $this->startTest($test);
            $this->testResults[$key]->execution->setStatus($status);
            $this->testResults[$key]->execution->finish();

            $this->handleMessage($key, $message);
            $this->reporter->addResult($this->testResults[$key]);

            return;
        }

        $this->testResults[$key]->execution->setStatus($status);
        $this->handleMessage($key, $message);

        if ($stackTrace) {
            $this->testResults[$key]->execution->setStackTrace($stackTrace);
        }
    }

    protected function handleMessage(string $key, ?string $message): void
    {
        if ($message) {
            $this->testResults[$key]->message = $this->testResults[$key]->message . "\n" . $message . "\n";
        }
    }

    public function completeTest(TestMethod $test): void
    {
        $key = $this->getTestKey($test);
        if (!isset($this->testResults[$key])) {
            return;
        }
        $this->testResults[$key]->execution->finish();

        $this->reporter->addResult($this->testResults[$key]);
        $this->currentKey = null;
    }

    protected function getTestKey(TestMethod $test): string
    {
        return $test->className() . '::' . $test->methodName() . ':' . $test->line();
    }

    protected function createSignature(TestMethod $test, ?array $ids = null, ?array $suites = null, ?array $params = null): string
    {
        $finalSuites = [];
        if ($suites) {
            $finalSuites = $suites;
        } else {
            $suites = explode('\\', $test->className());
            foreach ($suites as $suite) {
                $finalSuites[] = $suite;
            }
        }

        return Signature::generateSignature($ids, $finalSuites, $params);
    }

    protected function getThread(): string
    {
        return $_ENV['TEST_TOKEN'] ?? "default";
    }

    public function addComment(string $message): void
    {
        if (!$this->currentKey) {
            return;
        }

        $this->testResults[$this->currentKey]->message = $this->testResults[$this->currentKey]->message . $message . "\n";
    }

    public function updateTitle(string $title): void
    {
        if (!$this->currentKey) {
            return;
        }

        $this->testResults[$this->currentKey]->title = $title;
    }

    public function addAttachment(mixed $input): void
    {
        if (!$this->currentKey) {
            return;
        }

        if (is_string($input)) {
            $this->testResults[$this->currentKey]->attachments[] = Attachment::createFileAttachment($input);
            return;
        }

        if (is_array($input)) {
            foreach ($input as $item) {
                if (is_string($item)) {
                    $this->testResults[$this->currentKey]->attachments[] = Attachment::createFileAttachment($item);
                }
            }

            return;
        }

        if (is_object($input)) {
            $data = (array)$input;
            $this->testResults[$this->currentKey]->attachments[] = Attachment::createContentAttachment(
                $data['title'] ?? 'attachment',
                $data['content'] ?? null,
                $data['mime'] ?? null
            );
        }
    }
}
