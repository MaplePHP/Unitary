<?php
/**
 * DataTypeMock — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\TestUtils;

use ArrayIterator;
use MaplePHP\Log\InvalidArgumentException;

/**
 * A utility class for mocking different data types in unit tests.
 * Provides functionality to generate mock values for various PHP data types,
 * handle custom default values, and convert values to string representations.
 * This class is particularly useful for testing type-specific functionality
 * and generating test data with specific data types.
 */
final class DataTypeMock
{

    /**
     * @var array Stores custom default values for data types
     */
    private array $defaultArguments = [];

    /**
     * @var array|null Cache of stringifies data type values
     */
    private ?array $types = null;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $bindArguments = null;

    private static ?self $inst = null;

    public static function inst(): self
    {
        if (self::$inst === null) {
            self::$inst = new self();
        }
        return self::$inst;
    }

    /**
     * Returns an array of default arguments for different data types
     *
     * @return array Array of default arguments with mock values for different data types
     */
    public function getMockValues(): array
    {
        return array_merge([
            'int' => 123456,
            'float' => 3.14,
            'string' => "mockString",
            'bool' => true,
            'array' => ['item1', 'item2', 'item3'],
            'object' => (object)['item1' => 'value1', 'item2' => 'value2', 'item3' => 'value3'],
            'resource' => "fopen('php://memory', 'r+')",
            'callable' => fn() => 'called',
            'iterable' => new ArrayIterator(['a', 'b']),
            'null' => null,
        ], $this->defaultArguments);
    }
    
    /**
     * Exports a value to a parsable string representation
     *
     * @param mixed $value The value to be exported
     * @return string The string representation of the value
     */
    public static function exportValue(mixed $value): string
    {
        return var_export($value, true);
        
    }
    
    /**
     * Creates a new instance with merged default and custom arguments.
     * Handles resource type arguments separately by converting them to string content.
     *
     * @param array $dataTypeArgs Custom arguments to merge with defaults
     * @return self New instance with updated arguments
     */
    public function withCustomDefaults(array $dataTypeArgs): self
    {
        $inst = clone $this;
        foreach($dataTypeArgs as $key => $value) {
            $inst = $this->withCustomDefault($key, $value);
        }
        return $inst;
    }


    /**
     * Sets a custom default value for a specific data type.
     * If the value is a resource, it will be converted to its string content.
     *
     * @param string $dataType The data type to set the custom default for
     * @param mixed $value The value to set as default for the data type
     * @return self New instance with updated custom default
     */
    public function withCustomDefault(string $dataType, mixed $value): self
    {
        $inst = clone $this;
        if(isset($value) && is_resource($value)) {
            $value = $this->handleResourceContent($value);
        }
        $inst->defaultArguments[$dataType] = $value;
        return $inst;
    }

    /**
     * Sets a custom default value for a specific data type with a binding key.
     * Creates a new instance with the bound value stored in the bindArguments array.
     *
     * @param string $key The binding key to store the value under
     * @param string $dataType The data type to set the custom default for
     * @param mixed $value The value to set as default for the data type
     * @return self New instance with the bound value
     */
    public function withCustomBoundDefault(string $key, string $dataType, mixed $value): self
    {
        $inst = clone $this;
        $tempInst = $this->withCustomDefault($dataType, $value);
        if($inst->bindArguments === null) {
            $inst->bindArguments = [];
        }
        $inst->bindArguments[$key][$dataType] = $tempInst->defaultArguments[$dataType];
        return $inst;
    }
    
    /**
     * Converts default argument values to their string representations
     * using var_export for each value in the default arguments array
     *
     * @return array Array of stringify default argument values
     */
    public function getDataTypeListToString(): array
    {
        return array_map(fn($value) => self::exportValue($value), $this->getMockValues());
    }

    /**
     * Retrieves the string representation of a value for a given data type
     * Initializes types' array if not already set
     *
     * @param string $dataType The data type to get the value for
     * @return string The string representation of the value for the specified data type
     * @throws InvalidArgumentException If the specified data type is invalid
     */
    public function getDataTypeValue(string $dataType, ?string $bindKey = null): string
    {
        if(is_string($bindKey) && isset($this->bindArguments[$bindKey][$dataType])) {
            return self::exportValue($this->bindArguments[$bindKey][$dataType]);
        }

        if($this->types === null) {
            $this->types = $this->getDataTypeListToString();
        }

        if(!isset($this->types[$dataType])) {
            throw new InvalidArgumentException("Invalid data type: $dataType");
        }
        return (string)$this->types[$dataType];
        
    }
    
    /**
     * Will return a streamable content
     *
     * @param mixed $resourceValue
     * @return string|null
     */
    public function handleResourceContent(mixed $resourceValue): ?string
    {
        if (!is_resource($resourceValue)) {
            return null;
        }
        return var_export(stream_get_contents($resourceValue), true);
    }
}