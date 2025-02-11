# Qase TMS PHPUnit Reporter

Publish test results easily and efficiently.

## Installation

To install the latest version, run:

```sh
composer require qase/phpunit-reporter 
```

Add the following lines to the `phpunit.xml` file:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="./vendor/autoload.php">
    <extensions>
        <bootstrap class="Qase\PHPUnitReporter\QaseExtension"/>
    </extensions>
</phpunit>
```

## Getting Started

The PHPUnit reporter can auto-generate test cases and suites based on your test data.
Test results of subsequent test runs will match the same test cases as long as their names and file paths don’t change.

You can also annotate tests with the IDs of existing test cases from Qase.io before executing them.
This is a more reliable way to bind automated tests to test cases, ensuring they persist when you rename, move, or
parameterize your tests.

For example:

```php
<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\Parameter;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Attributes\Suite;
use Qase\PHPUnitReporter\Attributes\Title;
use Qase\PHPUnitReporter\Qase;

#[Suite("Main suite")]
class SimplesTest extends TestCase
{
    #[
        Title('Test one'),
        Parameter("param1", "value1"),
    ]
    public function testOne(): void
    {
        Qase::comment("My comment");
        $this->assertTrue(true);
    }

    #[
        QaseId(123),
        Field('description', 'Some description'),
        Field('severity', 'major')
    ]
    public function testTwo(): void
    {
        Qase::attach("/my_path/file.json");
        $this->assertTrue(false);
    }

    #[
        Suite('Suite one'),
        Suite('Suite two')
    ]
    public function testThree(): void
    {
        Qase::attach((object) ['title' => 'attachment.txt', 'content' => 'Some string', 'mime' => 'text/plain']);
        throw new Exception('Some exception');
    }
}
```

To execute PHPUnit tests and report them to Qase.io, run the command:

```bash
QASE_MODE=testops ./vendor/bin/phpunit
```

or, if configured in a script:

```bash
composer test
```

A test run will be created and accessible at:

https://app.qase.io/run/QASE_PROJECT_CODE

### Parallel Execution

The reporter supports parallel execution of tests using the `paratest` package.

To run tests in parallel, use the following command:

```bash
QASE_MODE=testops ./vendor/bin/paratest
```

## Configuration

Qase PHPUnit Reporter can be configured using:

1. A separate configuration file qase.config.json.
2. Environment variables (which override the values in the configuration file).

For a full list of configuration options, refer to
the [Configuration Reference](https://github.com/qase-tms/qase-php-commons/blob/main/README.md#configuration).

Example qase.config.json

```json
{
  "mode": "testops",
  "debug": true,
  "testops": {
    "api": {
      "token": "api_key"
    },
    "project": "project_code",
    "run": {
      "complete": true
    }
  }
}
```

## Requirements

We maintain the reporter on LTS versions of PHP.

- php >= 8.1
- phpunit >= 10

