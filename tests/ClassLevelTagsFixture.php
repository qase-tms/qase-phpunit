<?php

declare(strict_types=1);

namespace Tests;

use Qase\PHPUnitReporter\Attributes\Tags;

#[Tags('smoke')]
class ClassLevelTagsFixture
{
    #[Tags('regression')]
    public function testWithMethodTags(): void {}

    public function testWithoutTags(): void {}
}
