<?php

namespace Qase\PHPUnitReporter\Attributes;

use Qase\PHPUnitReporter\Models\Metadata;

interface AttributeParserInterface
{
    public function parseAttribute(string $className, string $methodName): Metadata;
}
