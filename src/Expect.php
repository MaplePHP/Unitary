<?php

/**
 * Expect — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary;

use Exception;
use Throwable;
use MaplePHP\Validate\ValidationChain;

/**
 * @api
 */
class Expect extends ValidationChain
{
    protected mixed $initValue = null;
    protected Throwable|false|null $except = null;
    private ?TestCase $testCase = null;
    private ?array $trace = null;

    /**
     * Static init validation chain
     *
     * @param mixed $value
     * @return mixed
     */
    public static function value(mixed $value): self
    {
        return new self($value);
    }

    /**
     * We need to pass a test case to Exception to create one loop
     *
     * @param TestCase $testCase
     * @param array $trace We can change the trace
     * @return $this
     */
    public function setTestCase(TestCase $testCase, array $trace): self
    {
        $this->testCase = $testCase;
        $this->trace = $trace;
        return $this;
    }

    /**
     * Set a test from array
     *
     * @param TestUnit $test
     * @param array $listArr
     * @param string|null $message
     * @return TestUnit
     */
    public function setTestFeed(TestUnit $test, array $listArr, ?string $message = null): TestUnit
    {
        foreach ($listArr as $list) {
            if (is_bool($list)) {
                $item = new TestItem();
                $item = $item->setIsValid($list)->setValidation("Validation");
                $test->setTestItem($item);
            } else {
                foreach ($list as $method => $valid) {
                    $item = new TestItem();
                    /** @var array|bool $valid */
                    $item = $item->setIsValid(false)->setValidation((string)$method);
                    if (is_array($valid)) {
                        $item = $item->setValidationArgs($valid);
                    } else {
                        $item = $item->setHasArgs(false);
                    }
                    $test->setTestItem($item);
                }
            }
        }
        return $test;
    }

    /**
     * Validate the test feed
     *
     * @param string|null $description
     * @return void
     * @throws \ErrorException
     */
    public function validate(?string $description = null): void
    {
        $test = new TestUnit($description);
        $test = $this->setTestFeed($test, $this->getError(), $description);
        $test->setTestValue($this->testCase?->getCurrentValue($this));
        $this->testCase?->setTest($test, $this->trace);
    }

    /**
     * Validate and assert the test feed
     *
     * @param string|null $description
     * @return void
     * @throws \ErrorException
     */
    public function assert(?string $description = null): void
    {
        if ($this->testCase === null) {
            // Assert inherits the test configuration from below due to the internal flow of validate()
            assert($this->isValid(), $description);
        }
        $list = $this->getError();
        $test = new TestUnit($description);
        $test->setTestValue($this->testCase->getValue());
        $this->testCase?->assert($description);
        $this->testCase?->setTest($this->setTestFeed($test, $list, $description), $this->trace);
    }

    /**
     * Validate exception instance
     *
     * @param string|object|callable $compare
     * @return $this
     * @throws Exception
     */
    public function isThrowable(string|object|callable $compare): self
    {
        if ($except = $this->getException()) {
            $this->setValue($except);
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isClass((string)$compare));
        return $this;
    }

    /**
     * Validate exception message
     *
     * @param string|callable $compare
     * @return $this
     * @throws Exception
     */
    public function hasThrowableMessage(string|callable $compare): self
    {
        if ($except = $this->getException()) {
            $this->setValue($except->getMessage());
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Validate exception code
     *
     * @param int|callable $compare
     * @return $this
     * @throws Exception
     */
    public function hasThrowableCode(int|callable $compare): self
    {
        if ($except = $this->getException()) {
            $this->setValue($except->getCode());
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Validate exception Severity
     *
     * @param int|callable $compare
     * @return $this
     * @throws Exception
     */
    public function hasThrowableSeverity(int|callable $compare): self
    {
        if ($except = $this->getException()) {
            $value = method_exists($except, 'getSeverity') ? $except->getSeverity() : 0;
            $this->setValue($value);
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Validate exception file
     *
     * @param string|callable $compare
     * @return $this
     * @throws Exception
     */
    public function hasThrowableFile(string|callable $compare): self
    {
        if ($except = $this->getException()) {
            $this->setValue($except->getFile());
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Validate exception line
     *
     * @param int|callable $compare
     * @return $this
     * @throws Exception
     */
    public function hasThrowableLine(int|callable $compare): self
    {
        if ($except = $this->getException()) {
            $this->setValue($except->getLine());
        }
        /** @psalm-suppress PossiblyInvalidCast */
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Helper to validate the exception instance against the provided callable.
     *
     * @param string $name
     * @param string|int|object|callable $compare
     * @param callable $fall
     * @return self
     */
    protected function validateExcept(string $name, int|string|object|callable $compare, callable $fall): self
    {
        $pos = strrpos($name, '::');
        $name = ($pos !== false) ? substr($name, $pos + 2) : $name;
        $this->mapErrorValidationName($name);
        if (is_callable($compare)) {
            $compare($this);
        } else {
            $fall($this);
        }

        if (is_null($this->initValue)) {
            $this->initValue = $this->getValue();
        }

        if ($this->except === false) {
            $this->setValue(null);
        }
        return $this;
    }

    /**
     * Used to get the first value before any validation is performed and
     * any changes to the value are made.
     *
     * @return mixed
     */
    public function getInitValue(): mixed
    {
        return $this->initValue;
    }

    /**
     * Retrieves the exception instance if one has been caught,
     * otherwise attempts to invoke a callable value to detect any exception.
     *
     * @return Throwable|false Returns the caught exception if available, or false if no exception occurs.
     * @throws Exception Throws an exception if the provided value is not callable.
     */
    protected function getException(): Throwable|false
    {
        if (!is_null($this->except)) {
            return $this->except;
        }

        $expect = $this->getValue();
        if (!is_callable($expect)) {
            throw new Exception("Except method only accepts callable");
        }
        try {
            $expect();
            $this->except = false;
        } catch (Throwable $exception) {
            $this->except = $exception;
            return $this->except;
        }
        return false;
    }
}
