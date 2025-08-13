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
use MaplePHP\Unitary\Support\Helpers;

final class TestUnit
{
    private bool $valid;
    private mixed $value = null;
    private bool $hasValue = false;
    private ?string $message;
    private array $unit = [];
    private int $count = 0;
    private int $valLength = 0;
    private array $codeLine = ['line' => 0, 'code' => '', 'file' => ''];

    /**
     * Initiate the test
     *
     * @param string|null $message
     */
    public function __construct(?string $message = null)
    {
        $this->valid = true;
        $this->message = $message === null ? "Could not validate" : $message;
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
     * Create a test item
     *
     * @param TestItem $item
     * @return $this
     */
    public function setTestItem(TestItem $item): self
    {
        if (!$item->isValid()) {
            $this->valid = false;
            $this->count++;
        }

        $valLength = $item->getValidationLengthWithArgs();
        if ($this->valLength < $valLength) {
            $this->valLength = $valLength;
        }

        $this->unit[] = $item;
        return $this;
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
