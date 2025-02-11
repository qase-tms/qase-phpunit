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
}
