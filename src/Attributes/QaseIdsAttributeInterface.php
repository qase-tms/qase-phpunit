<?php

namespace Qase\PHPUnitReporter\Attributes;

interface QaseIdsAttributeInterface extends AttributeInterface
{
    public function getValue(): array;
}
