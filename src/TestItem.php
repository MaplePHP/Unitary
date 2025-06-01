<?php
/**
 * TestUnit — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary;

use Closure;
use MaplePHP\Unitary\Utils\Helpers;

final class TestItem
{

    protected bool $valid = false;
    protected string $validation = "";
    protected array $args = [];
    protected mixed $value = null;
    protected array $compareValues = [];


    public function __construct()
    {
    }

    public function setIsValid(bool $isValid): self
    {
        $inst = clone $this;
        $inst->valid = $isValid;
        return $inst;
    }

    public function setValidation(string $validation): self
    {
        $inst = clone $this;
        $inst->validation = $validation;
        return $inst;
    }

    public function setValidationArgs(array $args): self
    {
        $inst = clone $this;
        $inst->args = $args;
        return $inst;
    }

    public function setCompare(mixed $value, mixed ...$compareValue): self
    {
        $inst = clone $this;
        $inst->value = $value;
        $inst->compareValues = $compareValue;
        return $inst;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getValidation(): string
    {
        return $this->validation;
    }

    public function getValidationArgs(): array
    {
        return $this->args;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function hasComparison(): bool
    {
        return ($this->compareValues !== []);
    }

    public function getCompareValues(): mixed
    {
        return $this->compareValues;
    }

    public function getComparison(): string
    {
        return "Expected: " . $this->getValue() . " | Actual: " . implode(":", $this->getCompareValues());
    }

    public function getStringifyArgs(): string
    {
        return Helpers::stringifyArgs($this->args);
    }

    public function getValidationLength(): int
    {
        return strlen($this->getValidation());
    }
}
