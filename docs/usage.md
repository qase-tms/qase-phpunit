# Qase PHPUnit Reporter - Usage Guide

This guide covers all available annotations and methods for using the Qase PHPUnit Reporter.

## Table of Contents

- [Annotations](#annotations)
  - [QaseId](#qaseid)
  - [QaseIds](#qaseids)
  - [Title](#title)
  - [Suite](#suite)
  - [Field](#field)
  - [Parameter](#parameter)
- [Methods](#methods)
  - [Qase::comment()](#qasecomment)
  - [Qase::attach()](#qaseattach)
- [Examples](#examples)
- [Configuration](#configuration)
- [Running Tests](#running-tests)

## Annotations

### QaseId

Links a test method to a specific Qase test case by ID.

**Target:** Method only  
**Repeatable:** No

```php
#[QaseId(123)]
public function testUserLogin(): void
{
    // Test implementation
}
```

### QaseIds

Links a test method to multiple Qase test cases by IDs.

**Target:** Method only  
**Repeatable:** No

```php
#[QaseIds([1, 2, 3])]
public function testMultipleScenarios(): void
{
    // Test implementation
}
```

**Note:** All IDs must be integers. Non-integer values will be filtered out.

### Title

Sets a custom title for the test case in Qase.

**Target:** Method only  
**Repeatable:** No

```php
#[Title('User Login with Valid Credentials')]
public function testUserLogin(): void
{
    // Test implementation
}
```

### Suite

Assigns the test to one or more test suites in Qase.

**Target:** Method and Class  
**Repeatable:** Yes

```php
// Single suite
#[Suite('Authentication')]
public function testUserLogin(): void
{
    // Test implementation
}

// Multiple suites
#[
    Suite('Authentication'),
    Suite('User Management')
]
public function testUserLogin(): void
{
    // Test implementation
}

// Class-level suite (applies to all methods)
#[Suite('API Tests')]
class ApiTest extends TestCase
{
    // All test methods will be assigned to 'API Tests' suite
}
```

### Field

Adds custom fields to the test case in Qase.

**Target:** Method and Class  
**Repeatable:** Yes

```php
#[
    Field('description', 'Tests user login functionality'),
    Field('severity', 'high'),
    Field('priority', 'P1')
]
public function testUserLogin(): void
{
    // Test implementation
}
```

### Parameter

Adds parameters to the test case in Qase.

**Target:** Method only  
**Repeatable:** Yes

```php
#[
    Parameter('username', 'test@example.com'),
    Parameter('password', 'password123'),
    Parameter('browser', 'chrome')
]
public function testUserLogin(): void
{
    // Test implementation
}
```

## Methods

### Qase::comment()

Adds a comment to the current test case.

```php
public function testUserLogin(): void
{
    Qase::comment("Starting user login test");
    
    // Test implementation
    
    Qase::comment("User login test completed successfully");
}
```

### Qase::attach()

Adds attachments to the current test case. Supports multiple formats:

#### File Attachment (Single File)

```php
public function testWithScreenshot(): void
{
    // Take screenshot
    $screenshotPath = '/path/to/screenshot.png';
    
    Qase::attach($screenshotPath);
}
```

#### Multiple File Attachments

```php
public function testWithMultipleFiles(): void
{
    $files = [
        '/path/to/screenshot.png',
        '/path/to/log.txt',
        '/path/to/data.json'
    ];
    
    Qase::attach($files);
}
```

#### Content Attachment

```php
public function testWithContent(): void
{
    $attachment = (object) [
        'title' => 'Test Data',
        'content' => '{"user": "test", "status": "active"}',
        'mime' => 'application/json'
    ];
    
    Qase::attach($attachment);
}
```

## Examples

### Complete Example

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\Parameter;
use Qase\PHPUnitReporter\Attributes\QaseId;
use Qase\PHPUnitReporter\Attributes\Suite;
use Qase\PHPUnitReporter\Attributes\Title;
use Qase\PHPUnitReporter\Qase;

#[Suite('Authentication')]
class UserAuthenticationTest extends TestCase
{
    #[
        QaseId(123),
        Title('User Login with Valid Credentials'),
        Field('description', 'Tests user login with valid email and password'),
        Field('severity', 'high'),
        Parameter('username', 'test@example.com'),
        Parameter('password', 'password123')
    ]
    public function testValidLogin(): void
    {
        Qase::comment("Starting valid login test");
        
        // Test implementation
        $this->assertTrue(true);
        
        // Add screenshot
        Qase::attach('/path/to/screenshot.png');
        
        Qase::comment("Valid login test completed");
    }

    #[
        QaseIds([124, 125]),
        Title('User Login with Invalid Credentials'),
        Suite('Negative Testing'),
        Field('severity', 'medium')
    ]
    public function testInvalidLogin(): void
    {
        Qase::comment("Testing invalid login scenarios");
        
        // Test implementation
        $this->assertFalse(false);
        
        // Add multiple files
        Qase::attach([
            '/path/to/error_log.txt',
            '/path/to/validation_errors.json'
        ]);
    }

    #[Title('User Logout')]
    public function testLogout(): void
    {
        Qase::comment("Testing user logout functionality");
        
        // Test implementation
        
        // Add content attachment
        $logoutData = (object) [
            'title' => 'Logout Response',
            'content' => '{"status": "success", "message": "Logged out"}',
            'mime' => 'application/json'
        ];
        
        Qase::attach($logoutData);
    }
}
```

### Class-Level Configuration

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\PHPUnitReporter\Attributes\Field;
use Qase\PHPUnitReporter\Attributes\Suite;

#[
    Suite('API Tests'),
    Field('environment', 'staging'),
    Field('api_version', 'v2')
]
class ApiTest extends TestCase
{
    // All test methods inherit the suite and fields from class annotations
    
    public function testGetUser(): void
    {
        // This test will be assigned to 'API Tests' suite
        // and have environment=staging, api_version=v2 fields
    }
    
    public function testCreateUser(): void
    {
        // Same inheritance applies here
    }
}
```

## Best Practices

### 1. Use Descriptive Titles

```php
// Good
#[Title('User Login with Valid Email and Password')]

// Avoid
#[Title('Test 1')]
```

### 2. Organize with Suites

```php
// Group related tests
#[Suite('Authentication')]
#[Suite('User Management')]
```

### 3. Use Meaningful Field Names

```php
#[
    Field('test_type', 'integration'),
    Field('component', 'user_management'),
    Field('priority', 'P1')
]
```

### 4. Add Comments for Complex Tests

```php
public function testComplexWorkflow(): void
{
    Qase::comment("Step 1: Initialize test data");
    // ... implementation
    
    Qase::comment("Step 2: Execute main workflow");
    // ... implementation
    
    Qase::comment("Step 3: Verify results");
    // ... implementation
}
```

### 5. Attach Relevant Files

```php
public function testWithEvidence(): void
{
    // Test implementation
    
    // Attach relevant files
    Qase::attach([
        '/path/to/request.log',
        '/path/to/response.json',
        '/path/to/screenshot.png'
    ]);
}
```

### 6. Combine Annotations Effectively

```php
#[
    QaseId(123),
    Title('User Login with Valid Credentials'),
    Suite('Authentication'),
    Suite('Smoke Tests'),
    Field('severity', 'high'),
    Field('priority', 'P1'),
    Parameter('browser', 'chrome'),
    Parameter('environment', 'staging')
]
public function testUserLogin(): void
{
    // Test implementation
}
```

## Configuration

Make sure your `phpunit.xml` includes the Qase extension:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="./vendor/autoload.php">
    <extensions>
        <bootstrap class="Qase\PHPUnitReporter\QaseExtension"/>
    </extensions>
</phpunit>
```

## Running Tests

To run tests with Qase reporting:

```bash
QASE_MODE=testops ./vendor/bin/phpunit
```

For parallel execution:

```bash
QASE_MODE=testops ./vendor/bin/paratest
```
