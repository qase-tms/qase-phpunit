<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Qase;

class AttachmentTest extends TestCase
{
    #[QaseId(30)]
    public function testFileAttachment(): void
    {
        Qase::attach(__DIR__ . '/testdata/sample.txt');
        $this->assertTrue(true);
    }

    #[QaseId(31)]
    public function testContentAttachment(): void
    {
        Qase::attach((object) [
            'title' => 'log.json',
            'content' => '{"key": "value"}',
            'mime' => 'application/json',
        ]);
        $this->assertTrue(true);
    }
}
