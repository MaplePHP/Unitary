# MaplePHP - Unitary

PHP Unitary is a **user-friendly** and robust unit testing library designed to make writing and running tests for your PHP code easy. With an intuitive CLI interface that works on all platforms and robust validation options, Unitary makes it easy for you as a developer to ensure your code is reliable and functions as intended.

![Prompt demo](http://wazabii.se/github-assets/maplephp-unitary.png)

### Syntax You Will Love
```php
$unit->case("MaplePHP Request URI path test", function() {
    $response = new Response(200);

    $this->add($response->getStatusCode(), function() {
        return $this->equal(200);
    }, "HTTP Request method type is not POST");
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

// If you build your library correctly, it will become very easy to mock, as I have below.
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

## Configurations

### Select a Test File to Run

After each test, a hash key is shown, allowing you to run specific tests instead of all.

```bash
php vendor/bin/unitary --show=b0620ca8ef6ea7598eaed56a530b1983
```

### Run Test Case Manually

You can also mark a test case to run manually, excluding it from the main test batch.

```php
$unit->manual('maplePHPRequest')->case("MaplePHP Request URI path test", function() use($request) {
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

7. **isFile**
    - **Description**: Checks if the value is a valid file.
    - **Usage**: `"isFile" => []`

8. **isDir**
    - **Description**: Checks if the value is a valid directory.
    - **Usage**: `"isDir" => []`

9. **isResource**
    - **Description**: Checks if the value is a valid resource.
    - **Usage**: `"isResource" => []`

10. **number**
    - **Description**: Checks if the value is numeric.
    - **Usage**: `"number" => []`

### Equality and Length Checks
11. **equal**
    - **Description**: Checks if the value is equal to a specified value.
    - **Usage**: `"equal" => ["someValue"]`

12. **notEqual**
    - **Description**: Checks if the value is not equal to a specified value.
    - **Usage**: `"notEqual" => ["someValue"]`

13. **length**
    - **Description**: Checks if the string length is between a specified start and end length.
    - **Usage**: `"length" => [1, 200]`

14. **equalLength**
    - **Description**: Checks if the string length is equal to a specified length.
    - **Usage**: `"equalLength" => [10]`

### Numeric Range Checks
15. **min**
    - **Description**: Checks if the value is greater than or equal to a specified minimum.
    - **Usage**: `"min" => [10]`

16. **max**
    - **Description**: Checks if the value is less than or equal to a specified maximum.
    - **Usage**: `"max" => [100]`

17. **positive**
    - **Description**: Checks if the value is a positive number.
    - **Usage**: `"positive" => []`

18. **negative**
    - **Description**: Checks if the value is a negative number.
    - **Usage**: `"negative" => []`

### String and Pattern Checks
19. **pregMatch**
    - **Description**: Validates if the value matches a given regular expression pattern.
    - **Usage**: `"pregMatch" => ["a-zA-Z"]`

20. **atoZ (lower and upper)**
    - **Description**: Checks if the value consists of characters between `a-z` or `A-Z`.
    - **Usage**: `"atoZ" => []`

21. **lowerAtoZ**
    - **Description**: Checks if the value consists of lowercase characters between `a-z`.
    - **Usage**: `"lowerAtoZ" => []`

22. **upperAtoZ**
    - **Description**: Checks if the value consists of uppercase characters between `A-Z`.
    - **Usage**: `"upperAtoZ" => []`

23. **hex**
    - **Description**: Checks if the value is a valid hex color code.
    - **Usage**: `"hex" => []`

24. **email**
    - **Description**: Validates email addresses.
    - **Usage**: `"email" => []`

25. **url**
    - **Description**: Checks if the value is a valid URL (http|https is required).
    - **Usage**: `"url" => []`

26. **phone**
    - **Description**: Validates phone numbers.
    - **Usage**: `"phone" => []`

27. **zip**
    - **Description**: Validates ZIP codes within a specified length range.
    - **Usage**: `"zip" => [5, 9]`

28. **domain**
    - **Description**: Checks if the value is a valid domain.
    - **Usage**: `"domain" => [true]`

29. **dns**
    - **Description**: Checks if the host/domain has a valid DNS record (A, AAAA, MX).
    - **Usage**: `"dns" => []`

30. **matchDNS**
    - **Description**: Matches DNS records by searching for a specific type and value.
    - **Usage**: `"matchDNS" => [DNS_A]`

31. **lossyPassword**
    - **Description**: Validates a password with allowed characters `[a-zA-Z\d$@$!%*?&]` and a minimum length.
    - **Usage**: `"lossyPassword" => [8]`

32. **strictPassword**
    - **Description**: Validates a strict password with specific character requirements and a minimum length.
    - **Usage**: `"strictPassword" => [8]`

### Required and Boolean-Like Checks
33. **required**
    - **Description**: Checks if the value is not empty (e.g., not `""`, `0`, `NULL`).
    - **Usage**: `"required" => []`

34. **isBoolVal**
    - **Description**: Checks if the value is a boolean-like value (e.g., "on", "yes", "1", "true").
    - **Usage**: `"isBoolVal" => []`

35. **hasValue**
    - **Description**: Checks if the value itself is interpreted as having value (e.g., 0 is valid).
    - **Usage**: `"hasValue" => []`

36. **isNull**
    - **Description**: Checks if the value is null.
    - **Usage**: `"isNull" => []`

### Date and Time Checks
37. **date**
    - **Description**: Checks if the value is a valid date with the specified format.
    - **Usage**: `"date" => ["Y-m-d"]`

38. **dateTime**
    - **Description**: Checks if the value is a valid date and time with the specified format.
    - **Usage**: `"dateTime" => ["Y-m-d H:i"]`

39. **time**
    - **Description**: Checks if the value is a valid time with the specified format.
    - **Usage**: `"time" => ["H:i"]`

40. **age**
    - **Description**: Checks if the value represents an age equal to or greater than the specified minimum.
    - **Usage**: `"age" => [18]`

### Version Checks
41. **validVersion**
    - **Description**: Checks if the value is a valid version number.
    - **Usage**: `"validVersion" => [true]`

42. **versionCompare**
    - **Description**: Validates and compares if a version is equal/more/equalMore/less than a specified version.
    - **Usage**: `"versionCompare" => ["1.0.0", ">="]`

### Logical Checks
43. **oneOf**
    - **Description**: Validates if one of the provided conditions is met.
    - **Usage**: `"oneOf" => [["length", [1, 200]], "email"]`

44. **allOf**
    - **Description**: Validates if all the provided conditions are met.
    - **Usage**: `"allOf" => [["length", [1, 200]], "email"]`

### Additional Validations

45. **creditCard**
    - **Description**: Validates credit card numbers.
    - **Usage**: `"creditCard" => []`

56. **vatNumber**
    - **Description**: Validates Swedish VAT numbers.
    - **Usage**: `"vatNumber" => []`
