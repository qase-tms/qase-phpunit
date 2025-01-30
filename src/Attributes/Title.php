<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set title for a test
 * Example:
 * #[Title('Test one')]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Title implements TitleAttributeInterface
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
