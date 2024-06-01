Here's an improved version of your markdown guide for your PHP testing library:

# Unitary
**PHP Unitary** is a lightweight PHP testing library.

## Example

### 1. Create a Test File
Start by creating a test file with a name that starts with "unitary-", e.g., "unitary-lib-name.php".
```php
<?php
// If you add the argument "true" to the Unit class, it will run in quiet mode
// and only report if it finds any errors.
$unit = new MaplePHP\Unitary\Unit(true);

// Add a title to your tests (optional)
$unit->addTitle("Testing MaplePHP Unitary library!");

$unit->add("Checking data type", function($inst) {

    $inst->add("Lorem ipsum dolor", [
        "string" => [],
        "length" => [1, 200]

    ])->add(92928, [
        "int" => []

    ])->add("Lorem", [
        "string" => [],
        "length" => function($valid) {
            return $valid->length(1, 50);
        }
    ], "The length is not correct!");

});

$unit->execute();
```
The example above uses either built-in validation or custom validation (see below for all built-in validation options).

You can create as many test files as you want, then move on to step 2.

### 2. Create a Bash File
Create an executable bash file. You can name it whatever you like; in this example, it's named "test.php".
```php
#!/usr/bin/env php
<?php
require_once("vendor/autoload.php");
$unit = new MaplePHP\Unitary\Unit();
$unit->executeAll("path/to/directory/");
```

#### Run the Tests
1. Open your terminal.
2. Navigate to the "test.php" file.
3. Execute the command below to run all tests.
```
php test.php
```

The script will recursively find all test files named "unitary-*.php" in the directory "path/to/directory/" and run all the tests.


## Validation List

Each prompt can have validation rules and custom error messages. Validation can be defined using built-in rules (e.g., length, email) or custom functions. Errors can be specified as static messages or dynamic functions based on the error type.

1. **required**
    - **Description**: Checks if the value is not empty (e.g., not `""`, `0`, `NULL`).
    - **Usage**: `"required" => []`

2. **length**
    - **Description**: Checks if the string length is between a specified start and end length.
    - **Usage**: `"length" => [1, 200]`

3. **email**
    - **Description**: Validates email addresses.
    - **Usage**: `"email" => []`

4. **number**
    - **Description**: Checks if the value is numeric.
    - **Usage**: `"number" => []`

5. **min**
    - **Description**: Checks if the value is greater than or equal to a specified minimum.
    - **Usage**: `"min" => [10]`

6. **max**
    - **Description**: Checks if the value is less than or equal to a specified maximum.
    - **Usage**: `"max" => [100]`

7. **url**
    - **Description**: Checks if the value is a valid URL (http|https is required).
    - **Usage**: `"url" => []`

8. **phone**
    - **Description**: Validates phone numbers.
    - **Usage**: `"phone" => []`

9. **date**
    - **Description**: Checks if the value is a valid date with the specified format.
    - **Usage**: `"date" => ["Y-m-d"]`

10. **dateTime**
    - **Description**: Checks if the value is a valid date and time with the specified format.
    - **Usage**: `"dateTime" => ["Y-m-d H:i"]`

11. **bool**
    - **Description**: Checks if the value is a boolean.
    - **Usage**: `"bool" => []`

12. **oneOf**
    - **Description**: Validates if one of the provided conditions is met.
    - **Usage**: `"oneOf" => [["length", [1, 200]], "email"]`

13. **allOf**
    - **Description**: Validates if all of the provided conditions are met.
    - **Usage**: `"allOf" => [["length", [1, 200]], "email"]`

14. **float**
    - **Description**: Checks if the value is a float.
    - **Usage**: `"float" => []`

15. **int**
    - **Description**: Checks if the value is an integer.
    - **Usage**: `"int" => []`

16. **positive**
    - **Description**: Checks if the value is a positive number.
    - **Usage**: `"positive" => []`

17. **negative**
    - **Description**: Checks if the value is a negative number.
    - **Usage**: `"negative" => []`

18. **validVersion**
    - **Description**: Checks if the value is a valid version number.
    - **Usage**: `"validVersion" => [true]`

19. **versionCompare**
    - **Description**: Validates and compares if a version is equal/more/equalMore/less... e.g., than withVersion.
    - **Usage**: `"versionCompare" => ["1.0.0", ">="]`

20. **zip**
    - **Description**: Validates ZIP codes within a specified length range.
    - **Usage**: `"zip" => [5, 9]`

21. **hex**
    - **Description**: Checks if the value is a valid hex color code.
    - **Usage**: `"hex" => []`

22. **age**
    - **Description**: Checks if the value represents an age equal to or greater than the specified minimum.
    - **Usage**: `"age" => [18]`

23. **domain**
    - **Description**: Checks if the value is a valid domain.
    - **Usage**: `"domain" => [true]`

24. **dns**
    - **Description**: Checks if the host/domain has a valid DNS record (A, AAAA, MX).
    - **Usage**: `"dns" => []`

25. **matchDNS**
    - **Description**: Matches DNS records by searching for a specific type and value.
    - **Usage**: `"matchDNS" => [DNS_A]`

26. **equal**
    - **Description**: Checks if the value is equal to a specified value.
    - **Usage**: `"equal" => ["someValue"]`

27. **notEqual**
    - **Description**: Checks if the value is not equal to a specified value.
    - **Usage**: `"notEqual" => ["someValue"]`

28. **string**
    - **Description**: Checks if the value is a string.
    - **Usage**: `"string" => []`

29. **equalLength**
    - **Description**: Checks if the string length is equal to a specified length.
    - **Usage**: `"equalLength" => [10]`

30. **lossyPassword**
    - **Description**: Validates password with allowed characters `[a-zA-Z\d$@$!%*?&]` and a minimum length.
    - **Usage**: `"lossyPassword" => [8]`

31. **strictPassword**
    - **Description**: Validates strict password with specific character requirements and a minimum length.
    - **Usage**: `"strictPassword" => [8]`

32. **pregMatch**
    - **Description**: Validates if the value matches a given regular expression pattern.
    - **Usage**: `"pregMatch" => ["a-zA-Z"]`

33. **atoZ**
    - **Description**: Checks if the value consists of characters between `a-z` or `A-Z`.
    - **Usage**: `"atoZ" => []`

34. **lowerAtoZ**
    - **Description**: Checks if the value consists of lowercase characters between `a-z`.
    - **Usage**: `"lowerAtoZ" => []`

35. **upperAtoZ**
    - **Description**: Checks if the value consists of uppercase characters between `A-Z`.
    - **Usage**: `"upperAtoZ" => []`

36. **isArray**
    - **Description**: Checks if the value is an array.
    - **Usage**: `"isArray" => []`

37. **isObject**
    - **Description**: Checks if the value is an object.
    - **Usage**: `"isObject" => []`

38. **boolVal**
    - **Description**: Checks if the value is a boolean-like value (e.g., "on", "yes", "1", "true").
    - **Usage**: `"boolVal" => []`