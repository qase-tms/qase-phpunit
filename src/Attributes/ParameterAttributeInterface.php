<?php

namespace Qase\PHPUnitReporter\Attributes;

interface ParameterAttributeInterface extends AttributeInterface
{
    public function getName(): string;

    public function getValue(): string;
}
