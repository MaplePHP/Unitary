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
    private int $valLength = 0;
    private array $codeLine = ['line' => 0, 'code' => '', 'file' => ''];

    /**
     * Initiate the test
     *
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
     *
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
        
        $valLength = strlen((string)$validation);
        if($validation && $this->valLength < $valLength) {
            $this->valLength = $valLength;
        }
        
        $this->unit[] = [
            'valid' => $valid,
            'validation' => $validation,
            'args' => $args
        ];
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
    function setCodeLine(array $trace): self
    {
        $this->codeLine = [];
        $file = $trace['file'] ?? '';
        $line = $trace['line'] ?? 0;
        if ($file && $line) {
            $lines = file($file);
            $code = trim($lines[$line - 1] ?? '');
            if(str_starts_with($code, '->')) {
                $code = substr($code, 2);
            }
            $code = $this->excerpt($code);

            $this->codeLine['line'] = $line;
            $this->codeLine['file'] = $file;
            $this->codeLine['code'] = $code;
        }
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
     * Get failed test count
     *
     * @return int
     */
    public function getFailedTestCount(): int
    {
        return $this->count;
    }

    /**
     * Get test message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get if test is valid
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

    /**
     * Used to get a readable value
     *
     * @return string
     * @throws ErrorException
     */
    public function getReadValue(): string
    {
        if (is_bool($this->value)) {
            return '"' . ($this->value ? "true" : "false") . '"' . " (type: bool)";
        }
        if (is_int($this->value)) {
            return '"' . $this->excerpt((string)$this->value) . '"' . " (type: int)";
        }
        if (is_float($this->value)) {
            return '"' . $this->excerpt((string)$this->value) . '"' . " (type: float)";
        }
        if (is_string($this->value)) {
            return '"' . $this->excerpt($this->value) . '"' . " (type: string)";
        }
        if (is_array($this->value)) {
            return '"' . $this->excerpt(json_encode($this->value)) . '"' . " (type: array)";
        }
        if (is_object($this->value)) {
            return '"' . $this->excerpt(get_class($this->value)) . '"' . " (type: object)";
        }
        if (is_null($this->value)) {
            return '"null"  (type: null)';
        }
        if (is_resource($this->value)) {
            return '"' . $this->excerpt(get_resource_type($this->value)) . '"' . " (type: resource)";
        }

        return "(unknown type)";
    }

    /**
     * Used to get exception to the readable value
     *
     * @param string $value
     * @param int $length
     * @return string
     * @throws ErrorException
     */
    final protected function excerpt(string $value, int $length = 80): string
    {
        $format = new Str($value);
        return (string)$format->excerpt($length)->get();
    }

}
