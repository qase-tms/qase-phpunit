<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

class Qase
{
    /*
     * Add comment to test case
     * @param string $message
     * @return void
     *
     * Example:
     * Qase::comment("My comment");
     */
    public static function comment(string $message): void
    {
        $qr = QaseReporter::getInstanceWithoutInit();
        if (!$qr) {
            return;
        }

        $qr->addComment($message);
    }

    /* Add attachment to test case
     * @param mixed $input
     * @return void
     *
     * Example:
     * Qase::attach("/my_path/file.json");
     * Qase::attach(["/my_path/file.json", "/my_path/file2.json"]);
     * Qase::attach((object) ['title' => 'attachment.txt', 'content' => 'Some string', 'mime' => 'text/plain']);
     */
    public static function attach(mixed $input): void
    {
        $qr = QaseReporter::getInstanceWithoutInit();
        if (!$qr) {
            return;
        }

        $qr->addAttachment($input);
    }
}
