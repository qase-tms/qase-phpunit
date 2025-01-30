<?php

namespace Qase\PHPUnitReporter\Attributes;

interface SuiteAttributeInterface extends AttributeInterface
{
    public function getValue(): string;
}
