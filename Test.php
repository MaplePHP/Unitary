<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

use MaplePHP\DTO\Format\Str;
use MaplePHP\Validate\Inp;
use InvalidArgumentException;

class Test
{
    private array $test = [];

    /**
     * Add a test
     * @param mixed $value
     * @param array $validation
     * @param string|null $message
     * @return $this
     */
    public function add(mixed $value, array $validation, ?string $message = null): self
    {
        foreach($validation as $method => $args) {
            if(is_callable($args)) {
                $bool = $args($this->valid($value), $value);
                if(!is_bool($bool)) {
                    throw new InvalidArgumentException("A callable validation must return a boolean!");
                }
            } else {
                if(!method_exists(Inp::class, $method)) {
                    throw new InvalidArgumentException("The validation {$method} does not exist!");
                }

                if(!is_array($args)) {
                    $args = [];
                }
                $bool = $this->valid($value)->{$method}(...$args);
            }

            $readableValue = $this->getReadableValue($value);
            $msg = (is_string($message)) ? ", ($message)" : "";
            $this->test[] = [
                "method" => $method,
                "args" => $args,
                "test" => $bool,
                "message" => sprintf("Validation-error: %s", $method) . $msg,
                "readableValue" => $readableValue,
            ];
        }

        return $this;
    }

    /**
     * Will return the test results as an array
     * @return array
     */
    public function getTestResult(): array
    {
        return $this->test;
    }

    /**
     * Init MaplePHP validation
     * @param mixed $value
     * @return Inp
     */
    protected function valid(mixed $value): Inp {
        return new Inp($value);
    }

    /**
     * Used to get a readable value
     * @param mixed $value
     * @return string
     */
    protected function getReadableValue(mixed $value): string {
        if (is_bool($value)) {
            return "(bool): " . ($value ? "true" : "false");
        }
        if (is_int($value)) {
            return "(int): " . $this->excerpt((string)$value);
        }
        if (is_float($value)) {
            return "(float): " . $this->excerpt((string)$value);
        }
        if (is_string($value)) {
            return "(string): " . $this->excerpt($value);
        }
        if (is_array($value)) {
            return "(array): " . $this->excerpt(json_encode($value));
        }
        if (is_object($value)) {
            return "(object): " . $this->excerpt(get_class($value));
        }
        if (is_null($value)) {
            return "(null)";
        }
        if (is_resource($value)) {
            return "(resource): " . $this->excerpt(get_resource_type($value));
        }

        return "(unknown type)";
    }

    /**
     * Used to get exception to the readable value
     * @param string $value
     * @return string
     */
    final protected function excerpt(string $value): string
    {
        $format = new Str($value);
        return $format->excerpt(40)->get();
    }
}
