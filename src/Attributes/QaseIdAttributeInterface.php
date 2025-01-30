<?php

namespace Qase\PHPUnitReporter\Attributes;

interface QaseIdAttributeInterface extends AttributeInterface
{
    public function getValue(): int;
}
