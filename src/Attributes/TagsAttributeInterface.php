<?php

namespace Qase\PHPUnitReporter\Attributes;

interface TagsAttributeInterface extends AttributeInterface
{
    /**
     * @return string[]
     */
    public function getTags(): array;
}
