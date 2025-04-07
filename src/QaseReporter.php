<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

use PHPUnit\Event\Code\TestMethod;
use Qase\PhpCommons\Interfaces\ReporterInterface;
use Qase\PhpCommons\Models\Attachment;
use Qase\PhpCommons\Models\Result;
use Qase\PHPUnitReporter\Attributes\AttributeParserInterface;

class QaseReporter implements QaseReporterInterface
{
    private static QaseReporter $instance;
    private array $testResults = [];
    private AttributeParserInterface $attributeParser;
    private ReporterInterface $reporter;
    private ?string $currentKey = null;

    private function __construct(AttributeParserInterface $attributeParser, ReporterInterface $reporter)
    {
        $this->attributeParser = $attributeParser;
        $this->reporter = $reporter;
    }

    public static function getInstance(AttributeParserInterface $attributeParser, ReporterInterface $reporter): QaseReporter
    {
        if (!isset(self::$instance)) {
            self::$instance = new QaseReporter($attributeParser, $reporter);
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
        $this->currentKey = $key;

        $metadata = $this->attributeParser->parseAttribute($test->className(), $test->methodName());

        $testResult = new Result();

        if (!empty($metadata->qaseIds)) {
            $testResult->testOpsIds = $metadata->qaseIds;
        }

        if (empty($metadata->suites)) {
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
        $testResult->signature = $this->createSignature($test);
        $testResult->execution->setThread($this->getThread());

        $testResult->title = $metadata->title ?? $test->methodName();

        $this->testResults[$key] = $testResult;
    }

    public function updateStatus(TestMethod $test, string $status, ?string $message = null, ?string $stackTrace = null): void
    {
        $key = $this->getTestKey($test);
        $this->testResults[$key]->execution->setStatus($status);

        if ($message) {
            $this->testResults[$key]->message = $this->testResults[$key]->message . "\n" . $message . "\n";
        }

        if ($stackTrace) {
            $this->testResults[$key]->execution->setStackTrace($stackTrace);
        }
    }

    public function completeTest(TestMethod $test): void
    {
        $key = $this->getTestKey($test);
        $this->testResults[$key]->execution->finish();

        $this->reporter->addResult($this->testResults[$key]);
    }

    private function getTestKey(TestMethod $test): string
    {
        return $test->className() . '::' . $test->methodName() . ':' . $test->line();
    }

    private function createSignature(TestMethod $test): string
    {
        return str_replace("\\", "::", $test->className()) . '::' . $test->methodName() . ':' . $test->line();
    }

    private function getThread(): string
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
