<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set parameter for a test
 * Example:
 * #[
 *      Parameter("param01", "value01"),
 *      Parameter("param02", "value02")
 * ]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Parameter implements ParameterAttributeInterface
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
