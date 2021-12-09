<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

class ConsoleLogger
{
    public function write(string $message, string $prefix = '[Qase reporter]'): void
    {
        if ($prefix) {
            $message = sprintf('%s %s', $prefix, $message);
        }

        print $message;
    }

    public function writeln(string $message, string $prefix = '[Qase reporter]'): void
    {
        $this->write($message, $prefix);
        print PHP_EOL;
    }
}
