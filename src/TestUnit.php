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
use MaplePHP\DTO\Format\Str;
use MaplePHP\Unitary\Utils\Helpers;

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
     * Set the test unit
     *
     * @param bool|null $valid can be null if validation should execute later
     * @param string|null|\Closure $validation
     * @param array|bool $args
     * @param array $compare
     * @return $this
     * @throws ErrorException
     */
    public function setUnit(
        bool|null $valid,
        null|string|\Closure $validation = null,
        array|bool $args = [],
        array $compare = []
    ): self {

        if (!$valid) {
            $this->valid = false;
            $this->count++;
        }

        if (is_string($validation)) {
            $addArgs = is_array($args) ? "(" . Helpers::stringifyArgs($args) . ")" : "";
            $valLength = strlen($validation . $addArgs);
            if ($validation && $this->valLength < $valLength) {
                $this->valLength = $valLength;
            }
        }

        if ($compare && count($compare) > 0) {
            $compare = array_map(fn ($value) => $this->getReadValue($value, true), $compare);
        }
        $this->unit[] = [
            'valid' => $valid,
            'validation' => $validation,
            'args' => $args,
            'compare' => $compare
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
    public function setCodeLine(array $trace): self
    {
        $this->codeLine = [];
        $file = (string)($trace['file'] ?? '');
        $line = (int)($trace['line'] ?? 0);
        $lines = file($file);
        $code = "";
        if($lines !== false) {
            $code = trim($lines[$line - 1] ?? '');
            if (str_starts_with($code, '->')) {
                $code = substr($code, 2);
            }
            $code = $this->excerpt($code);
        }

        $this->codeLine['line'] = $line;
        $this->codeLine['file'] = $file;
        $this->codeLine['code'] = $code;

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

    /**
     * Used to get a readable value
     *
     * @param mixed|null $value
     * @param bool $minify
     * @return string
     * @throws ErrorException
     */
    public function getReadValue(mixed $value = null, bool $minify = false): string
    {
        $value = $value === null ? $this->value : $value;
        if (is_bool($value)) {
            return '"' . ($value ? "true" : "false") . '"' . ($minify ? "" : " (type: bool)");
        }
        if (is_int($value)) {
            return '"' . $this->excerpt((string)$value) . '"' . ($minify ? "" : " (type: int)");
        }
        if (is_float($value)) {
            return '"' . $this->excerpt((string)$value) . '"' . ($minify ? "" : " (type: float)");
        }
        if (is_string($value)) {
            return '"' . $this->excerpt($value) . '"' . ($minify ? "" : " (type: string)");
        }
        if (is_array($value)) {
            $json = json_encode($value);
            if($json === false) {
                return "(unknown type)";
            }
            return '"' . $this->excerpt($json) . '"' . ($minify ? "" : " (type: array)");
        }
        if (is_callable($value)) {
            return '"' . $this->excerpt(get_class((object)$value)) . '"' . ($minify ? "" : " (type: callable)");
        }
        if (is_object($value)) {
            return '"' . $this->excerpt(get_class($value)) . '"' . ($minify ? "" : " (type: object)");
        }
        if ($value === null) {
            return '"null"'. ($minify ? '' : ' (type: null)');
        }
        if (is_resource($value)) {
            return '"' . $this->excerpt(get_resource_type($value)) . '"' . ($minify ? "" : " (type: resource)");
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
