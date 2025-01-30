<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter\Attributes;

use ReflectionClass;
use ReflectionMethod;

interface AttributeReaderInterface
{
    function getClassAnnotations(ReflectionClass $class, ?string $name = null): array;

    public function getMethodAnnotations(ReflectionMethod $method, ?string $name = null): array;
}
