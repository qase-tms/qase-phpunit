<?php

declare(strict_types=1);

namespace Tests;

use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Attributes\Suite;
use Qase\PHPUnitReporter\Attributes\Tags;
use Qase\PHPUnitReporter\Attributes\Title;

class TagsTestFixture
{
    #[Tags('smoke', 'regression')]
    public function testWithSingleTagsAttribute(): void {}

    #[Tags('smoke')]
    #[Tags('regression')]
    public function testWithMultipleTagsAttributes(): void {}

    public function testWithoutTags(): void {}

    #[QaseId(100)]
    #[Title('Custom title')]
    #[Suite('Auth')]
    #[Field('severity', 'high')]
    #[Tags('smoke', 'regression')]
    public function testWithAllAttributes(): void {}
}
