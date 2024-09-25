<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

use BadMethodCallException;
use Closure;
use ErrorException;
use MaplePHP\Validate\Inp;
use RuntimeException;
use Throwable;

class TestCase
{
    private mixed $value;
    private ?string $message;
    private array $test;
    private int $count = 0;
    private Closure $bind;

    function __construct(?string $message = null)
    {
        $this->message = $message;
    }

    /**
     * Bind the test case to the Closure
     * @param Closure $bind
     * @return void
     */
    function bind(Closure $bind): void
    {
        $this->bind = $bind->bindTo($this);
    }

    /**
     * Will dispatch the case tests and return them as an array
     * @return array
     */
    function dispatchTest(): array
    {
        $test = $this->bind;
        $test($this);
        return $this->test;
    }

    /**
     * Create a test
     * @param mixed $expect
     * @param array|Closure $validation
     * @param string|null $message
     * @return TestCase
     * @throws ErrorException
     */
    function add(mixed $expect, array|Closure $validation, ?string $message = null): self
    {
        $this->value = $expect;
        $test = new TestUnit($this->value, $message);
        if(is_callable($validation)) {
            $test->setUnit($this->buildClosureTest($validation));
        } else {
            foreach($validation as $method => $args) {
                if(!is_callable($args) && !is_array($args)) {
                    $args = [$args];
                }
                $test->setUnit($this->buildArrayTest($method, $args), $method, (is_array($args) ? $args : []));
            }
        }
        if(!$test->isValid()) {
            $this->count++;
        }
        $this->test[] = $test;
        return $this;
    }


    /**
     * Get failed test counts
     * @return int
     */
    public function getTotal(): int
    {
        return count($this->test);
    }

    /**
     * Get failed test counts
     * @return int
     */
    public function getCount(): int
    {
        return $this->getTotal() -  $this->getFailedCount();
    }

    /**
     * Get failed test counts
     * @return int
     */
    public function getFailedCount(): int
    {
        return $this->count;
    }

    /**
     * Check if it has failed tests
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->getFailedCount() > 0;
    }

    /**
     * Get original value
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get user added message
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get test array object
     * @return array
     */
    public function getTest(): array
    {
        return $this->test;
    }

    /**
     * This will build the closure test
     * @param Closure $validation
     * @return bool
     * @throws ErrorException
     */
    public function buildClosureTest(Closure $validation): bool
    {
        $validation = $validation->bindTo($this->valid($this->value));
        $bool = $validation($this->value);
        if(!is_bool($bool)) {
            throw new RuntimeException("A callable validation must return a boolean!");
        }

        if(is_null($this->message)) {
            throw new RuntimeException("When testing with closure the third argument message is required");
        }

        return $bool;
    }

    /**
     * This will build the array test
     * @param string $method
     * @param array|Closure $args
     * @return bool
     * @throws ErrorException
     */
    public function buildArrayTest(string $method, array|Closure $args): bool
    {
        if(is_callable($args)) {
            $args = $args->bindTo($this->valid($this->value));
            $bool = $args($this->value);
            if(!is_bool($bool)) {
                throw new RuntimeException("A callable validation must return a boolean!");
            }
        } else {
            if(!method_exists(Inp::class, $method)) {
                throw new BadMethodCallException("The validation $method does not exist!");
            }
            if(!is_array($args)) {
                $args = [];
            }
            try {
                $bool = call_user_func_array([$this->valid($this->value), $method], $args);
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $bool;
    }

    /**
     * Init MaplePHP validation
     * @param mixed $value
     * @return Inp
     * @throws ErrorException
     */
    protected function valid(mixed $value): Inp {
        return new Inp($value);
    }



}
