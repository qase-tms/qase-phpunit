<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\AttributeParser;
use Qase\PHPUnitReporter\Attributes\AttributeReader;
use Qase\PhpCommons\Loggers\Logger;

class AttributeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AttributeParser(new Logger(), new AttributeReader());
    }

    public function testParseTagsFromSingleAttribute(): void
    {
        $metadata = $this->parser->parseAttribute(TagsTestFixture::class, 'testWithSingleTagsAttribute');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testParseTagsFromMultipleAttributes(): void
    {
        $metadata = $this->parser->parseAttribute(TagsTestFixture::class, 'testWithMultipleTagsAttributes');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testMergeClassAndMethodTags(): void
    {
        $metadata = $this->parser->parseAttribute(ClassLevelTagsFixture::class, 'testWithMethodTags');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testClassTagsInheritedByMethodWithoutTags(): void
    {
        $metadata = $this->parser->parseAttribute(ClassLevelTagsFixture::class, 'testWithoutTags');
        $this->assertSame(['smoke'], $metadata->tags);
    }

    public function testEmptyTags(): void
    {
        $metadata = $this->parser->parseAttribute(TagsTestFixture::class, 'testWithoutTags');
        $this->assertSame([], $metadata->tags);
    }

    public function testAllAttributesTogether(): void
    {
        $metadata = $this->parser->parseAttribute(TagsTestFixture::class, 'testWithAllAttributes');
        $this->assertSame('Custom title', $metadata->title);
        $this->assertSame([100], $metadata->qaseIds);
        $this->assertSame(['Auth'], $metadata->suites);
        $this->assertSame(['severity' => 'high'], $metadata->fields);
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }
}
