<?php

namespace Qase\PHPUnitReporter\Attributes;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class AttributeReader implements AttributeReaderInterface
{
    /**
     * @param ReflectionClass $class
     * @param string|null $name
     * @return array<AttributeInterface>
     */
    function getClassAnnotations(ReflectionClass $class, ?string $name = null): array
    {
        return $this->getAttributeInstances(...$class->getAttributes($name));
    }

    /**
     * @param ReflectionMethod $method
     * @param string|null $name
     * @return array<AttributeInterface>
     */
    public function getMethodAnnotations(ReflectionMethod $method, ?string $name = null): array
    {
        return $this->getAttributeInstances(...$method->getAttributes($name));
    }

    private function getAttributeInstances(ReflectionAttribute ...$attributes): array
    {
        /** @psalm-var array<ReflectionAttribute<AttributeInterface>> $filteredAttributes */
        $filteredAttributes = array_filter(
            $attributes,
            fn(ReflectionAttribute $attribute): bool => class_exists($attribute->getName()) &&
                is_a($attribute->getName(), AttributeInterface::class, true),
        );

        return array_map(
            fn(ReflectionAttribute $attribute): AttributeInterface => $attribute->newInstance(),
            array_values($filteredAttributes),
        );
    }
}
