<?php

namespace Qase\PHPUnitReporter\Attributes;


use Qase\PhpCommons\Interfaces\LoggerInterface;
use Qase\PHPUnitReporter\Models\Metadata;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class AttributeParser implements AttributeParserInterface
{
    private AttributeReaderInterface $reader;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, AttributeReaderInterface $reader)
    {

        $this->reader = $reader;
        $this->logger = $logger;
    }

    public function parseAttribute(string $className, string $methodName): Metadata
    {
        try {
            $classRef = $this->getReflectionClass($className);
            $methodRef = $this->getReflectionMethod($className, $methodName);

            $annotations = [
                ...$this->reader->getClassAnnotations($classRef),
                ...$this->reader->getMethodAnnotations($methodRef)
            ];

            return $this->getMetadataFromAnnotations($annotations);
        } catch (ReflectionException $e) {
            $this->logger->error("Annotations not loaded: {$e->getMessage()}");
            return new Metadata();
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getReflectionClass(string $className): ReflectionClass
    {
        return new ReflectionClass($className);
    }

    /**
     * @throws ReflectionException
     */
    private function getReflectionMethod(string $className, string $methodName): ReflectionMethod
    {
        return new ReflectionMethod($className, $methodName);
    }

    private function getMetadataFromAnnotations(array $annotations): Metadata
    {
        $metadata = new Metadata();

        foreach ($annotations as $annotation) {
            if ($annotation instanceof TitleAttributeInterface) {
                $metadata->title = $annotation->getValue();
            }

            if ($annotation instanceof QaseIdAttributeInterface) {
                $metadata->qaseIds[] = $annotation->getValue();
            }

            if ($annotation instanceof QaseIdsAttributeInterface) {
                $metadata->qaseIds = array_merge($metadata->qaseIds, $annotation->getValue());
            }

            if ($annotation instanceof SuiteAttributeInterface) {
                $metadata->suites[] = $annotation->getValue();
            }

            if ($annotation instanceof FieldAttributeInterface) {
                $metadata->fields[$annotation->getName()] = $annotation->getValue();
            }

            if ($annotation instanceof ParameterAttributeInterface) {
                $metadata->parameters[$annotation->getName()] = $annotation->getValue();
            }
        }

        return $metadata;
    }
}
