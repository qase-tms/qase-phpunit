<?php

namespace Qase\PHPUnitReporter\Attributes;

interface TitleAttributeInterface extends AttributeInterface
{
    public function getValue(): string;
}
