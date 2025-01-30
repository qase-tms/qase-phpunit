<?php

namespace Qase\PHPUnitReporter\Attributes;

interface FieldAttributeInterface extends AttributeInterface
{
    public function getName(): string;

    public function getValue(): string;
}
