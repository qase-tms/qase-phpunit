<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set Qase ID for a test
 * Example:
 * #[QaseId(123)]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class QaseId implements QaseIdAttributeInterface
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
