# MaplePHP - Unitary

PHP Unitary is a **user-friendly** and robust unit testing library designed to make writing and running tests for your PHP code easy. With an intuitive CLI interface that works on all platforms and robust validation options, Unitary makes it easy for you as a developer to ensure your code is reliable and functions as intended.

![Prompt demo](http://wazabii.se/github-assets/maplephp-unitary.png)
_Do you like the CLI theme? [Download it here](https://github.com/MaplePHP/DarkBark)_


### Syntax You Will Love
```php
$unit->case("MaplePHP Request URI path test", function() {
    $response = new Response(200);

    $this->add($response->getStatusCode(), function() {
        return $this->equal(200);
    }, "Did not return HTTP status code 200");
});
```

## Documentation
The documentation is divided into several sections:
- [Installation](#installation)
- [Guide](#guide)
    - [1. Create a Test File](#1-create-a-test-file)
    - [2. Create a Test Case](#2-create-a-test-case)
    - [3. Run the Tests](#3-run-the-tests)
- [Configurations](#configurations)
- [Validation List](#validation-list)
    - [Data Type Checks](#data-type-checks)
    - [Equality and Length Checks](#equality-and-length-checks)
    - [Numeric Range Checks](#numeric-range-checks)
    - [String and Pattern Checks](#string-and-pattern-checks)
    - [Required and Boolean-Like Checks](#required-and-boolean-like-checks)
    - [Date and Time Checks](#date-and-time-checks)
    - [Version Checks](#version-checks)
    - [Logical Checks](#logical-checks)


## Installation

To install MaplePHP Unitary, run the following command:

```bash
composer require --dev maplephp/unitary
```

## Guide

### 1. Create a Test File

Unitary will, by default, find all files prefixed with "unitary-" recursively from your project's root directory (where your "composer.json" file exists). The vendor directory will be excluded by default.

Start by creating a test file with a name that starts with "unitary-", e.g., "unitary-request.php". You can place the file inside your library directory, for example like this: `tests/unitary-request.php`.

**Note: All of your library classes will automatically be autoloaded through Composer's autoloader inside your test file!**

### 2. Create a Test Case

Now that we have created a test file, e.g., `tests/unitary-request.php`, we will need to add our test cases and tests. I will create a test for one of my other libraries below, which is MaplePHP/HTTP, specifically the Request library that has full PSR-7 support.

I will show you three different ways to test your application below.

```php
<?php

$unit = new MaplePHP\Unitary\Unit();

$request = new MaplePHP\Http\Request(
    "GET",
    "https://admin:mypass@example.com:65535/test.php?id=5221&greeting=hello",
);

// Begin by adding a test case
$unit->case("MaplePHP Request URI path test", function() use($request) {

    // Then add tests to your case:
    // Test 1: Access the validation instance inside the add closure
    $this->add($request->getMethod(), function($value) {
        return $this->equal("GET");

    }, "HTTP Request method type does not equal GET");
    // Adding an error message is not required, but it is highly recommended.

    // Test 2: Built-in validation shortcuts
    $this->add($request->getUri()->getPort(), [
        "isInt" => [], // Has no arguments = empty array
        "min" => [1], // The strict way is to pass each argument as an array item
        "max" => 65535, // If it's only one argument, then this is acceptable too
        "length" => [1, 5]

    ], "Is not a valid port number");

    // Test 3: It is also possible to combine them all in one. 
    $this->add($request->getUri()->getUserInfo(), [
        "isString" => [],
        "User validation" => function($value) {
            $arr = explode(":", $value);
            return ($this->withValue($arr[0])->equal("admin") && $this->withValue($arr[1])->equal("mypass"));
        }

    ], "Did not get the expected user info credentials");
});
```

The example above uses both built-in validation and custom validation (see below for all built-in validation options).

### 3. Run the Tests

Now you are ready to execute the tests. Open your command line of choice, navigate (cd) to your project's root directory (where your `composer.json` file exists), and execute the following command:

```bash
php vendor/bin/unitary
```

#### The Output:
![Prompt demo](http://wazabii.se/github-assets/maplephp-unitary-result.png)
*And that is it! Your tests have been successfully executed!*

With that, you are ready to create your own tests!


## Mocking
Unitary comes with a built-in mocker that makes it super simple for you to mock classes.


### Auto mocking
What is super cool with Unitary Mocker will try to automatically mock the class that you pass and 
it will do it will do it quite accurate as long as the class and its methods that you are mocking is 
using data type in arguments and return type. 

```php
$unit->group("Testing user service", function (TestCase $inst) {
    
    // Just call the unitary mock and pass in class name
    $mock = $inst->mock(Mailer::class);
    // Mailer class is not mocked!
    
    // Pass argument to Mailer constructor e.g. new Mailer('john.doe@gmail.com', 'John Doe');
    //$mock = $inst->mock([Mailer::class, ['john.doe@gmail.com', 'John Doe']);
    // Mailer class is not mocked again!

    // Then just pass the mocked library to what ever service or controller you wish
    $service = new UserService($mock);
});
```
_Why? Sometimes you just want to quick mock so that a Mailer library will not send a mail_ 

### Custom mocking
As I said Unitary mocker will try to automatically mock every method but might not successes in some user-cases
then you can just tell Unitary how those failed methods should load.

```php
use MaplePHP\Validate\ValidationChain;
use \MaplePHP\Unitary\Mocker\MethodPool;

$unit->group("Testing user service", function (TestCase $inst) {
    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        // Quick way to tell Unitary that this method should return 'john.doe'
        $pool->method("getFromEmail")->willReturn('john.doe@gmail.com');

        // Or we can acctually pass a callable to it and tell it what it should return 
        // But we can also validate the argumnets!
        $pool->method("addFromEmail")->wrap(function($email) use($inst) {
            $inst->validate($email, function(ValidationChain $valid) {
                $valid->email();
                $valid->isString();
            });
            return true;
        });
    });
    
    // Then just pass the mocked library to what ever service or controller you wish
    $service = new UserService($mock);
});
```

### Mocking: Add Consistency validation
What is really cool is that you can also use Unitary mocker to make sure consistencies is followed and 
validate that the method is built and loaded correctly.

```php
use \MaplePHP\Unitary\Mocker\MethodPool;

$unit->group("Unitary test", function (TestCase $inst) {
    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addFromEmail")
            ->isPublic()
            ->hasDocComment()
            ->hasReturnType()
            ->isTimes(1);
        
        $pool->method("addBCC")
            ->isPublic()
            ->isTimes(3);
    });
    $service = new UserService($mock);
});
```


### Integration tests: Test Wrapper
Test wrapper is great to make integration test easier.

Most libraries or services has a method that executes the service and runs all the logic. The test wrapper we 
can high-jack that execution method and overwrite it with our own logic.

```php
$dispatch = $this->wrap(PaymentProcessor::class)->bind(function ($orderID) use ($inst) {
    // Simulate order retrieval
    $order = $this->orderService->getOrder($orderID);
    $response = $inst->mock('gatewayCapture')->capture($order->id);
    if ($response['status'] !== 'success') {
        // Log action within the PaymentProcessor instance
        $this->logger->info("Mocked: Capturing payment for Order ID: " . $order->id ?? 0);
        // Has successfully found order and logged message
        return true;
    }
    // Failed to find order
    return false;
});
```


## Configurations

### Show help
```bash
php vendor/bin/unitary --help
```

### Show only errors
```bash
php vendor/bin/unitary --errors-only
```

### Select a Test File to Run

After each test, a hash key is shown, allowing you to run specific tests instead of all.

```bash
php vendor/bin/unitary --show=b0620ca8ef6ea7598eaed56a530b1983
```

### Run Test Case Manually

You can also mark a test case to run manually, excluding it from the main test batch.

```php
$unit->manual('maplePHPRequest')->case("MaplePHP Request URI path test", function() {
    ...
});
```

And this will only run the manual test:
```bash
php vendor/bin/unitary --show=maplePHPRequest
```

### Change Test Path

The path argument takes both absolute and relative paths. The command below will find all tests recursively from the "tests" directory.

```bash
php vendor/bin/unitary --path="/tests/"
```

**Note: The `vendor` directory will be excluded from tests by default. However, if you change the `--path`, you will need to manually exclude the `vendor` directory.**

### Exclude Files or Directories

The exclude argument will always be a relative path from the `--path` argument's path.

```bash
php vendor/bin/unitary --exclude="./tests/unitary-query-php, tests/otherTests/*, */extras/*"
```

## Like The CLI Theme?
That’s DarkBark. Dark, quiet, confident, like a rainy-night synthwave playlist for your CLI.

[Download it here](https://github.com/MaplePHP/DarkBark)