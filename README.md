> # Qase TMS PHPUnit reporter
>
> Publish results simple and easy.

## How to integrate

```bash
composer require qase/phpunit-reporter
```

## Example of usage

The PHPUnit reporter has the ability to auto-generate test cases
and suites from your test data.

But if necessary, you can independently register the ID of already
existing test cases from TMS before the executing tests. For example:

```php
/**
 * @qaseId 3
 */
public function testCanBeUsedAsString(): void
{
    $this->assertEquals(
        'user@example.com',
        Email::fromString('user@example.com')
    );
}
```

You should also have an active item in the project settings at

```
https://app.qase.io/project/QASE_PROJECT_CODE/settings/options
```

options in the `Test Runs` block:

```
Auto create test cases
```
and
```
Allow submitting results in bulk
```

To run tests and create a test run, execute the command:

```bash
$ ./vendor/bin/phpunit 
```

![Output of run](example/screenshots/screenshot.png)

A test run will be performed and available at:

```
https://app.qase.io/run/QASE_PROJECT_CODE
```

### Configuration

Add to your `phpunit.xml` extension:

```xml
<extensions>
  <extension class="Qase\PHPUnit\Reporter"/>
</extensions>
```

Reporter options (* - required):

- *`QASE_API_TOKEN` - access token, you can find more information [here][auth].
- *`QASE_PROJECT_CODE` - code of your project (can be extracted from main page of your project,
  as example, for `https://app.qase.io/project/DEMO` -> `DEMO` is project code here.
- `QASE_API_BASE_URL` - URL endpoint API from Qase TMS, default is `https://api.qase.io/v1`.
- `QASE_RUN_ID` - allows you to use an existing test run instead of creating new.
- `QASE_COMPLETE_RUN_AFTER_SUBMIT` - performs the "complete" function after passing the test run.

The configuration file should be called `phpunit.xml`, an example of such a file:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <extensions>
    <extension class="Qase\PHPUnit\Reporter"/>
  </extensions>
  <testsuites>
    <testsuite name="qase-phpunit">
      <directory>./tests</directory>
    </testsuite>
  </testsuites>
  <php>
    <env name="QASE_PROJECT_CODE" value="project_code"/>
    <env name="QASE_API_BASE_URL" value="https://api.qase.io/v1"/>
    <env name="QASE_API_TOKEN" value="api_key"/>
    <env name="QASE_COMPLETE_RUN_AFTER_SUBMIT" value="1"/>
    <env name="QASE_RUN_ID"/>
  </php>
</phpunit>
```

<!-- references -->

[auth]: https://developers.qase.io/#authentication
