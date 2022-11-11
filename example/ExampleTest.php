<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{

    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    public function testSkipped(): void
    {
        $this->markTestSkipped();
    }

    public function testFail(): void
    {
        $this->assertTrue(false);
    }
}
