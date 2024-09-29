<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

use ErrorException;
use MaplePHP\DTO\Format\Str;

class TestUnit
{
    private bool $valid;
    private mixed $value;
    private ?string $message;
    private array $unit = [];
    private int $count = 0;

    /**
     * Initiate the test
     * @param mixed $value
     * @param string|null $message
     */
    public function __construct(mixed $value, ?string $message = null)
    {
        $this->valid = true;
        $this->value = $value;
        $this->message = is_null($message) ? "Could not validate" : $message;
    }

    /**
     * Set the test unit
     * @param bool $valid
     * @param string|null $validation
     * @param array $args
     * @return $this
     */
    public function setUnit(bool $valid, ?string $validation = null, array $args = []): self
    {
        if(!$valid) {
            $this->valid = false;
            $this->count++;
        }
        $this->unit[] = [
            'valid' => $valid,
            'validation' => $validation,
            'args' => $args
        ];
        return $this;
    }

    /**
     * Get ever test unit item array data
     * @return array
     */
    public function getUnits(): array
    {
        return $this->unit;
    }

    /**
     * Get failed test count
     * @return int
     */
    public function getFailedTestCount(): int
    {
        return $this->count;
    }

    /**
     * Get test message
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get if test is valid
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Gte the original value
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Used to get a readable value
     * @return string
     * @throws ErrorException
     */
    public function getReadValue(): string
    {
        if (is_bool($this->value)) {
            return "(bool): " . ($this->value ? "true" : "false");
        }
        if (is_int($this->value)) {
            return "(int): " . $this->excerpt((string)$this->value);
        }
        if (is_float($this->value)) {
            return "(float): " . $this->excerpt((string)$this->value);
        }
        if (is_string($this->value)) {
            return "(string): " . $this->excerpt($this->value);
        }
        if (is_array($this->value)) {
            return "(array): " . $this->excerpt(json_encode($this->value));
        }
        if (is_object($this->value)) {
            return "(object): " . $this->excerpt(get_class($this->value));
        }
        if (is_null($this->value)) {
            return "(null)";
        }
        if (is_resource($this->value)) {
            return "(resource): " . $this->excerpt(get_resource_type($this->value));
        }

        return "(unknown type)";
    }

    /**
     * Used to get exception to the readable value
     * @param string $value
     * @return string
     * @throws ErrorException
     */
    final protected function excerpt(string $value): string
    {
        $format = new Str($value);
        return (string)$format->excerpt(42)->get();
    }

}
