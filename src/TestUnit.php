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

use ErrorException;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Exceptions\BlunderSilentException;
use MaplePHP\Unitary\Console\Enum\UnitStatusType;
use MaplePHP\Unitary\Support\Helpers;

final class TestUnit
{
    private bool $valid;
    private bool $assert = false;
    private mixed $value = null;
    private bool $hasValue = false;
    private ?string $message;
    private ?string $validation = null;
    private array $unit = [];
    private int $count = 0;
    private int $valLength = 0;
    private array $codeLine = ['line' => 0, 'code' => '', 'file' => ''];
    private UnitStatusType $type = UnitStatusType::Failure;
    private ?ExceptionItem $throwable = null;
    /**
     * Initiate the test
     *
     * @param string|null $message
     */
    public function __construct(?string $message = null)
    {
        $this->valid = true;
        $this->message = $message === null ? "" : $message;
    }

    /**
     * Set as throwable
     *
     * @param ExceptionItem $throwable
     * @return void
     */
    public function setThrowable(ExceptionItem $throwable): void
    {
        $this->type = UnitStatusType::Error;
        $this->throwable = $throwable;
    }

    /**
     * Check if test has any PHP errors
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->type === UnitStatusType::Error;
    }

    /**
     * Check if is a failure or an error.
     *
     * @return UnitStatusType
     */
    public function getType(): UnitStatusType
    {
        return $this->type;
    }

    /**
     * If it has a thrown exception, then you can return it with this method.
     *
     * @return ExceptionItem|null
     */
    public function getThrowable(): ?ExceptionItem
    {
        return $this->throwable;
    }

    /**
     * Add custom error message if validation fails
     *
     * @param ?string $message
     * @return $this
     */
    public function describe(?string $message): self
    {
        if ($message !== null) {
            $this->message = $message;
        }
        return $this;
    }

    // Alias to describe
    public function error(?string $message): self
    {
        return $this->describe($message);
    }

    // Alias to describe
    public function message(?string $message): self
    {
        return $this->describe($message);
    }

    /**
     * Set validation as an asserting (Hard stop error)
     *
     * @param ?string $message
     * @return $this
     */
    public function assert(?string $message = null): self
    {
        $this->assert = true;
        $this->describe($message);
        assert($this->isValid(), new BlunderSilentException("Silence is gold..."));
        return $this;
    }

    /**
     * Get if validation is assertion
     *
     * @return bool
     */
    public function isAssert(): bool
    {
        return $this->assert;
    }

    public function setAsAssert(bool $isAssert = true): self
    {
        $this->assert = $isAssert;
        return $this;
    }

    /**
     * Check if the value should be presented
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    /**
     * Set a test value
     *
     * @param mixed $value
     * @return void
     */
    public function setTestValue(mixed $value): void
    {
        $this->value = $value;
        $this->hasValue = true;
    }


    /**
     * Set if validation is valid
     *
     * @param bool $isValid
     * @return void
     */
    public function setValid(bool $isValid): void
    {
        $this->valid = $isValid;
    }

    /**
     * Create a test item
     *
     * @param TestItem $item
     * @return $this
     */
    public function setTestItem(TestItem $item): self
    {
        if (!$item->isValid()) {
            $this->setValid(false);
            $this->count++;
        }

        $this->validation = $item->getValidation();

        $valLength = $item->getValidationLengthWithArgs();
        if ($this->valLength < $valLength) {
            $this->valLength = $valLength;
        }

        $this->unit[] = $item;
        return $this;
    }

    /**
     * Get the validation type
     *
     * @return ?string
     */
    public function getValidationMsg(): ?string
    {
        return $this->validation;
    }

    /**
     * Get the length of the validation string with the maximum length
     *
     * @return int
     */
    public function getValidationLength(): int
    {
        return $this->valLength;
    }

    /**
     * Set the code line from a backtrace
     *
     * @param array $trace
     * @return $this
     * @throws ErrorException
     */
    public function setCodeLine(array $trace): self
    {
        $this->codeLine = Helpers::getTrace($trace);
        return $this;
    }

    /**
     * Get the code line from a backtrace
     *
     * @return array
     */
    public function getCodeLine(): array
    {
        return $this->codeLine;
    }

    /**
     * Get ever test unit item array data
     *
     * @return array
     */
    public function getUnits(): array
    {
        return $this->unit;
    }

    /**
     * Get a failed test count
     *
     * @return int
     */
    public function getFailedTestCount(): int
    {
        return $this->count;
    }

    /**
     * Get a test message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get if the test is valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Gte the original value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
