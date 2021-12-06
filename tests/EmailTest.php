<?php declare(strict_types=1);

namespace Tests;

use App\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{

    /**
     * @qaseId 1
     */
    public function testCanBeCreatedFromValidEmailAddress(): void
    {
        $this->assertInstanceOf(
            Email::class,
            Email::fromString('user@example.com')
        );
    }

    /**
     * @qaseId 2
     */
    public function testCannotBeCreatedFromInvalidEmailAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('invalid');
    }

    /**
     * @qaseId 3
     */
    public function testCanBeUsedAsString(): void
    {
        $this->assertEquals(
            'user@example.com',
            Email::fromString('user@example.com')
        );
    }
}
