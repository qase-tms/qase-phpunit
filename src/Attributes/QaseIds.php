<?php

namespace Qase\PHPUnitReporter\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Set Qase IDs for a test. Multiple IDs can be specified in an array.
 * Example:
 * #[QaseIds([1,2,3])]
 * public function testOne(): void
 * {
 *    $this->assertTrue(true);
 * }
 * 
 * Note: All IDs must be integers. Non-integer values will be filtered out.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class QaseIds implements QaseIdsAttributeInterface
{
    private array $value;

    public function __construct(array $value)
    {
        $filtered = array_filter($value, static fn($id) => is_int($id));

        if (count($filtered) !== count($value)) {
            throw new InvalidArgumentException('QaseIds must contain only integers.');
        }

        $this->value = array_values($filtered);
    }

    public function getValue(): array
    {
        return $this->value;
    }
}
