<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set field for a test or a class
 * Example:
 * #[
 *      Field("description", "Some description"),
 *      Field("severity", "high")
 * ]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Field implements FieldAttributeInterface
{
    private string $value;
    private string $name;

    public function __construct(string $name, string $value)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
