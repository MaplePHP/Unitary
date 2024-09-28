# MaplePHP - Unitary

PHP Unitary is a **user-friendly** and pretty unit testing library designed to make writing and running tests for your PHP code easy. With an intuitive CLI interface that work on all platforms and robust validation options, Unitary makes it easy for you as a developer to ensure your code is reliable and functions as intended.


### Syntax you will love 
```php
$unit->case("MaplePHP Request URI path test", function() use($request) {
    $response = new Response(200);

    $this->add($response->getStatusCode(), function() {
        return $this->equal(200);
    }, "HTTP Request method Type is not POST");
});
```


## Documentation
The documentation is divided into several sections:
- [Installation](#installation)
- [Guide](#guide)
    - [Create a Test File](#1-create-a-test-file)
    - [Run the Tests](#2-run-the-tests)
    - [Configurations](#3-configurations)
- [Example Breakdown](#example-breakdown)
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

Unitary will, by default, find all files prefixed with "unitary-" recursively from your project's root directory (where your "composer.json" file exists), the vendor directory will be excluded by default.

Start by creating a test file with a name that starts with "unitary-", e.g., "unitary-request.php". You can place the file inside your library directory, for example like this `tests/unitary-request.php`.

**Note: All of your library classes will be automatically be autoloaded through the composers autoloader inside your test file!** 

### 2. Create a test case
Now that we have create a test file e.g. `tests/unitary-request.php`, we will need to add our test cases and tests. I will create a test for one a of my other libraries below, which is MaplePHP/HTTP and specifically the Request library that has full PSR-7 support.

I will show you three different ways to test your application below.

```php
<?php

$unit = new MaplePHP\Unitary\Unit();

// If you build your library right it will become very easy to mock, like I have below.
$request = new MaplePHP\Http\Request(
    "GET",
    "https://admin:mypass@example.com:65535/test.php?id=5221&greeting=hello",
);

// Begin by adding a test
$unit->case("MaplePHP Request URI path test", function() use($request) {

    // Test 1
    $this->add($request->getMethod(), function() {
        return $this->equal("GET");

    }, "HTTP Request method Type does not equal GET");
    // Adding a error message is not required, but it is highly recommended

    // Test 2
    $this->add($request->getUri()->getPort(), [
        "isInt" => [], // Has no arguments = empty array
        "min" => [1], // Strict way is to pass each argument to array
        "max" => 65535, // But if its only one argument then this it is acceptable
        "length" => [1, 5]

    ], "Is not a valid port number");

    // Test 3
    $this->add($request->getUri()->getUserInfo(), [
        "isString" => [],
        "User validation" => function($value) {
            $arr = explode(":", $value);
            return ($this->withValue($arr[0])->equal("admin") && $this->withValue($arr[1])->equal("mypass"));
        }

    ], "Did not get the expected user Info credentials");
});

$unit->execute();
```
The example above uses both built-in validation and custom validation (see below for all built-in validation options). 

### 2. Run the Tests

Now you are ready to execute the tests. Open your command line of choice, navigate (cd) to your project's root directory (where your `composer.json` file exists), and execute the following command:

```bash
php vendor/bin/unitary
```

And that is it. Your tests have been successfully executed.

And with that you are ready to create you own test!

## Configurations

You can change the default root testing path and exclude files or whole directories from the tests.

**Note: The `vendor` directory will be excluded from tests by default. However, if you change the `--path`, you will need to manually exclude the `vendor` directory.**

#### 1. Change Default Test Path

The path argument takes both absolute and relative paths. The command below will find all tests recursively from the "tests" directory.

```bash
php vendor/bin/unitary --path="/tests/"
```

#### 2. Exclude Specific Files or Directories

The exclude argument will always be a relative path from the `--path` argument's path.

```bash
php vendor/bin/unitary --exclude="./tests/unitary-query-php, tests/otherTests/*, */extras/*"
```

## Example Breakdown
This is a quick break down of the test file that you can read if you need. After the break-down I will list all available built in tests.

### Initial Setup
1. **Instantiate the Unit class**
    ```php
    $unit = new MaplePHP\Unitary\Unit();
    ```
    - **Purpose:** Creates an instance of the `Unit` class from the `MaplePHP\Unitary` namespace.
    - **Argument:** `true` enables quiet mode, which means it will only report errors and not print out all test results.

2. **Add a Title (Optional)**
    ```php
    $unit->addTitle("Testing MaplePHP Unitary library!");
    ```
    - **Purpose:** Adds a title to your test suite. This is optional and is used to give context or a name to your set of tests.

### Adding Tests
3. **Start Adding a Test Unit**
    ```php
    $unit->add("Checking data type", function($inst) {
        // Your test code here -->
    });
    ```
    - **Purpose:** Begins a new test unit block.
    - **Arguments:**
        - `"Checking data type"` is the name of this test block.
        - A callback function that contains the actual tests. The `$inst` parameter represents an instance within this test block.

### Adding Individual Tests
4. **Add Tests for `$myStrVar`**
    ```php
    $inst->add($myStrVar, [
        "isString" => [],
        "length" => [1, 200]
    ]);
    ```
    - **Purpose:** Add individual tests for `$myStrVar`.
    - **Arguments:**
        - `$myStrVar`: The variable being tested.
        - An array of validation rules:
            - `"isString" => []`: Checks if `$myStrVar` is a string.
            - `"length" => [1, 200]`: Checks if the length of `$myStrVar` is between 1 and 200 characters.

### Execute the Tests
8. **Execute All Tests**
    ```php
    $unit->execute();
    ```
    - **Purpose:** Runs all the tests that have been defined. If the tests pass, it will be silent due to quiet mode. If any test fails, it will report the errors.
      ...

## Validation List

Each prompt can have validation rules and custom error messages. Validation can be defined using built-in rules (e.g., length, email) or custom functions. Errors can be specified as static messages or dynamic functions based on the error type.

### Data Type Checks
1. **isString**
    - **Description**: Checks if the value is a string.
    - **Usage**: `"isString" => []`

2. **isInt**
    - **Description**: Checks if the value is an integer.
    - **Usage**: `"isInt" => []`

3. **isFloat**
    - **Description**: Checks if the value is a float.
    - **Usage**: `"isFloat" => []`

4. **isBool**
    - **Description**: Checks if the value is a boolean.
    - **Usage**: `"isBool" => []`

5. **isArray**
    - **Description**: Checks if the value is an array.
    - **Usage**: `"isArray" => []`

6. **isObject**
    - **Description**: Checks if the value is an object.
    - **Usage**: `"isObject" => []`

7. **number**
    - **Description**: Checks if the value is numeric.
    - **Usage**: `"number" => []`

### Equality and Length Checks
8. **equal**
    - **Description**: Checks if the value is equal to a specified value.
    - **Usage**: `"equal" => ["someValue"]`

9. **notEqual**
    - **Description**: Checks if the value is not equal to a specified value.
    - **Usage**: `"notEqual" => ["someValue"]`

10. **length**
    - **Description**: Checks if the string length is between a specified start and end length.
    - **Usage**: `"length" => [1, 200]`

11. **equalLength**
    - **Description**: Checks if the string length is equal to a specified length.
    - **Usage**: `"equalLength" => [10]`

### Numeric Range Checks
12. **min**
    - **Description**: Checks if the value is greater than or equal to a specified minimum.
    - **Usage**: `"min" => [10]`

13. **max**
    - **Description**: Checks if the value is less than or equal to a specified maximum.
    - **Usage**: `"max" => [100]`

14. **positive**
    - **Description**: Checks if the value is a positive number.
    - **Usage**: `"positive" => []`

15. **negative**
    - **Description**: Checks if the value is a negative number.
    - **Usage**: `"negative" => []`

### String and Pattern Checks
16. **pregMatch**
    - **Description**: Validates if the value matches a given regular expression pattern.
    - **Usage**: `"pregMatch" => ["a-zA-Z"]`

17. **atoZ**
    - **Description**: Checks if the value consists of characters between `a-z` or `A-Z`.
    - **Usage**: `"atoZ" => []`

18. **lowerAtoZ**
    - **Description**: Checks if the value consists of lowercase characters between `a-z`.
    - **Usage**: `"lowerAtoZ" => []`

19. **upperAtoZ**
    - **Description**: Checks if the value consists of uppercase characters between `A-Z`.
    - **Usage**: `"upperAtoZ" => []`

20. **hex**
    - **Description**: Checks if the value is a valid hex color code.
    - **Usage**: `"hex" => []`

21. **email**
    - **Description**: Validates email addresses.
    - **Usage**: `"email" => []`

22. **url**
    - **Description**: Checks if the value is a valid URL (http|https is required).
    - **Usage**: `"url" => []`

23. **phone**
    - **Description**: Validates phone numbers.
    - **Usage**: `"phone" => []`

24. **zip**
    - **Description**: Validates ZIP codes within a specified length range.
    - **Usage**: `"zip" => [5, 9]`

25. **domain**
    - **Description**: Checks if the value is a valid domain.
    - **Usage**: `"domain" => [true]`

26. **dns**
    - **Description**: Checks if the host/domain has a valid DNS record (A, AAAA, MX).
    - **Usage**: `"dns" => []`

27. **matchDNS**
    - **Description**: Matches DNS records by searching for a specific type and value.
    - **Usage**: `"matchDNS" => [DNS_A]`

28. **lossyPassword**
    - **Description**: Validates a password with allowed characters `[a-zA-Z\d$@$!%*?&]` and a minimum length.
    - **Usage**: `"lossyPassword" => [8]`

29. **strictPassword**
    - **Description**: Validates a strict password with specific character requirements and a minimum length.
    - **Usage**: `"strictPassword" => [8]`

### Required and Boolean-Like Checks
30. **required**
    - **Description**: Checks if the value is not empty (e.g., not `""`, `0`, `NULL`).
    - **Usage**: `"required" => []`

31. **isBoolVal**
    - **Description**: Checks if the value is a boolean-like value (e.g., "on", "yes", "1", "true").
    - **Usage**: `"isBoolVal" => []`

### Date and Time Checks
32. **date**
    - **Description**: Checks if the value is a valid date with the specified format.
    - **Usage**: `"date" => ["Y-m-d"]`

33. **dateTime**
    - **Description**: Checks if the value is a valid date and time with the specified format.
    - **Usage**: `"dateTime" => ["Y-m-d H:i"]`

34. **age**
    - **Description**: Checks if the value represents an age equal to or greater than the specified minimum.
    - **Usage**: `"age" => [18]`

### Version Checks
35. **validVersion**
    - **Description**: Checks if the value is a valid version number.
    - **Usage**: `"validVersion" => [true]`

36. **versionCompare**
    - **Description**: Validates and compares if a version is equal/more/equalMore/less than withVersion.
    - **Usage**: `"versionCompare" => ["1.0.0", ">="]`

### Logical Checks
37. **oneOf**
    - **Description**: Validates if one of the provided conditions is met.
    - **Usage**: `"oneOf" => [["length", [1, 200]], "email"]`

38. **allOf**
    - **Description**: Validates if all of the provided conditions are met.
    - **Usage**: `"allOf" => [["length", [1, 200]], "email"]`