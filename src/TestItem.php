<?php

/**
 * TestItem — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary;

use ErrorException;
use MaplePHP\Unitary\Support\Helpers;

final class TestItem
{
    protected bool $valid = false;
    protected string $validation = "";
    protected array $args = [];
    protected mixed $value = null;
    protected bool $hasArgs = true;
    protected array $compareValues = [];


    public function __construct()
    {
    }

    /**
     * Set if the test item is valid
     * @param bool $isValid
     * @return $this
     */
    public function setIsValid(bool $isValid): self
    {
        $inst = clone $this;
        $inst->valid = $isValid;
        return $inst;
    }

    /**
     * Sets the validation type that has been used.
     *
     * @param string $validation
     * @return $this
     */
    public function setValidation(string $validation): self
    {
        $inst = clone $this;
        $inst->validation = $validation;
        return $inst;
    }

    /**
     * Sets the validation arguments.
     *
     * @param array $args
     * @return $this
     */
    public function setValidationArgs(array $args): self
    {
        $inst = clone $this;
        $inst->args = $args;
        return $inst;
    }

    /**
     * Sets if the validation has arguments. If not, it will not be enclosed in parentheses.
     *
     * @param bool $enable
     * @return $this
     */
    public function setHasArgs(bool $enable): self
    {
        $inst = clone $this;
        $inst->hasArgs = $enable;
        return $inst;
    }

    /**
     * Sets the value that has been used in validation.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): self
    {
        $inst = clone $this;
        $inst->value = $value;
        return $inst;
    }

    /**
     * Sets a compare value for the current value.
     *
     * @param mixed ...$compareValue
     * @return $this
     */
    public function setCompareToValue(mixed ...$compareValue): self
    {
        $inst = clone $this;
        $inst->compareValues = $compareValue;
        return $inst;
    }

    /**
     * Converts the value to its string representation using a helper function.
     *
     * @return string The stringify representation of the value.
     * @throws ErrorException
     */
    public function getStringifyValue(): string
    {
        return Helpers::stringifyDataTypes($this->value, true);
    }

    /**
     * Converts the comparison values to their string representations using a helper function.
     *
     * @return array The array of stringify comparison values.
     * @throws ErrorException
     */
    public function getCompareToValue(): array
    {
        return array_map(fn ($value) => Helpers::stringifyDataTypes($value, true), $this->compareValues);
    }

    /**
     * Checks if the current state is valid.
     *
     * @return bool True if the state is valid, false otherwise.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Retrieves the validation string associated with the object.
     *
     * @return string The validation string.
     */
    public function getValidation(): string
    {
        return $this->validation;
    }

    /**
     * Retrieves the validation arguments.
     *
     * @return array The validation arguments.
     */
    public function getValidationArgs(): array
    {
        return $this->args;
    }

    /**
     * Retrieves the stored raw value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Determines if there are any comparison values present.
     *
     * @return bool
     */
    public function hasComparison(): bool
    {
        return ($this->compareValues !== []);
    }

    /**
     * Returns the RAW comparison collection.
     *
     * @return array
     */
    public function getCompareValues(): array
    {
        return $this->compareValues;
    }

    /**
     * Return a string representation of the comparison between expected and actual values.
     *
     * @return string
     * @throws ErrorException
     */
    public function getComparison(): string
    {
        return "Expected: " . $this->getStringifyValue() . " | Actual: " . implode(":", $this->getCompareToValue());
    }

    /**
     * Retrieves the string representation of the arguments, enclosed in parentheses if present.
     *
     * @return string
     */
    public function getStringifyArgs(): string
    {
        if ($this->hasArgs) {
            $args = array_map(fn ($value) => Helpers::stringifyArgs($value), $this->args);
            return "(" . implode(", ", $args) . ")";
        }
        return "";
    }

    /**
     * Retrieves the validation title by combining validation data and arguments.
     *
     * @return string
     */
    public function getValidationTitle(): string
    {
        return $this->getValidation() . $this->getStringifyArgs();
    }

    /**
     * Retrieves the length of the validation string.
     *
     * @return int
     */
    public function getValidationLength(): int
    {
        return strlen($this->getValidation());
    }

    /**
     * Retrieves the length of the validation title.
     *
     * @return int
     */
    public function getValidationLengthWithArgs(): int
    {
        return strlen($this->getValidationTitle());
    }
}
