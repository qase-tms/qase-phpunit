<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set suite for a test or a class
 * Example:
 * #[
 *      Suite("Main suite"),
 *      Suite("Sub suite")
 * ]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Suite implements SuiteAttributeInterface
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
