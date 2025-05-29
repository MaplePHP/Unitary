<?php

namespace MaplePHP\Unitary;

use Exception;
use Throwable;
use MaplePHP\Validate\ValidationChain;

class Expect extends ValidationChain
{

    protected mixed $initValue = null;
    protected Throwable|false|null $except = null;

    /**
     * Validate exception instance
     *
     * @param string|object|callable $compare
     * @return $this
     * @throws Exception
     */
    public function isThrowable(string|object|callable $compare): self
    {
        if($except = $this->getException()) {
            $this->setValue($except);
        }
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isClass($compare));
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
        if($except = $this->getException()) {
            $this->setValue($except->getMessage());
        }
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
        if($except = $this->getException()) {
            $this->setValue($except->getCode());
        }
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
        if($except = $this->getException()) {
            $value = method_exists($except, 'getSeverity') ? $except->getSeverity() : 0;
            $this->setValue($value);
        }
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
        if($except = $this->getException()) {
            $this->setValue($except->getFile());
        }
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
        if($except = $this->getException()) {
            $this->setValue($except->getLine());
        }
        $this->validateExcept(__METHOD__, $compare, fn() => $this->isEqualTo($compare));
        return $this;
    }

    /**
     * Helper to validate the exception instance against the provided callable.
     *
     * @param string $name
     * @param string|object|callable $compare
     * @param callable $fall
     * @return self
     */
    protected function validateExcept(string $name, string|object|callable $compare, callable $fall): self
    {
        $pos = strrpos($name, '::');
        $name = ($pos !== false) ? substr($name, $pos + 2) : $name;
        $this->mapErrorValidationName($name);
        if(is_callable($compare)) {
            $compare($this);
        } else {
            $fall($this);
        }

        if(is_null($this->initValue)) {
            $this->initValue = $this->getValue();
        }

        if($this->except === false) {
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

        if(!is_callable($this->getValue())) {
            throw new Exception("Except method only accepts callable");
        }
        try {
            $expect = $this->getValue();
            $expect();
            $this->except = false;
        } catch (Throwable $exception) {
            $this->except = $exception;
            return $this->except;
        }
        return false;

    }
}