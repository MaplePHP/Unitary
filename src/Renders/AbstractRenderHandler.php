<?php

namespace MaplePHP\Unitary\Renders;

use AssertionError;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\Handlers\CliHandler;
use Psr\Http\Message\StreamInterface;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Support\Helpers;
use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\TestItem;
use MaplePHP\Unitary\TestUnit;
use RuntimeException;

class AbstractRenderHandler implements BodyInterface
{
    protected TestCase $case;
    protected string $suitName = "";
    protected string $checksum = "";
    protected bool $show = false;
    protected bool $verbose = false;
    protected bool $alwaysShowFiles = false;
    protected array $tests;
    protected string $outputBuffer = "";
    protected StreamInterface $body;

    /**
     * {@inheritDoc}
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * {@inheritDoc}
     */
    public function setAlwaysShowFiles(bool $alwaysShowFiles): void
    {
        $this->alwaysShowFiles = $alwaysShowFiles;
    }

    /**
     * {@inheritDoc}
     */
    public function setCase(TestCase $testCase): void
    {
        $this->case = $testCase;
    }

    /**
     * {@inheritDoc}
     */
    public function setSuitName(string $title): void
    {
        $this->suitName = $title;
    }

    /**
     * {@inheritDoc}
     */
    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * {@inheritDoc}
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * {@inheritDoc}
     */
    public function setShow(bool $show): void
    {
        $this->show = $show;
    }

    /**
     * {@inheritDoc}
     */
    public function outputBuffer(string $addToOutput = ''): string
    {
        $out = (ob_get_level() > 0) ? ob_get_clean() : '';
        $this->outputBuffer = $out . $addToOutput;
        return $this->outputBuffer;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBody(): void
    {
        throw new RuntimeException('Your handler is missing the execution method.');
    }

    /**
     * {@inheritDoc}
     */
    public function buildNotes(): void
    {

        throw new RuntimeException('Your handler is missing the execution method.');
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Get error type
     *
     * @param TestUnit $test
     * @return string
     */
    public function getType(TestUnit $test): string
    {
        $throwable = $this->getThrowable($test);
        return $throwable !== null ? get_class($throwable->getException()) : "Validation error";
    }


    /**
     * Get a expected case name/message
     *
     * @param TestUnit $test
     * @return string
     */
    public function getCaseName(TestUnit $test): string
    {
        $msg = $test->getMessage();
        if($msg !== "") {
            return $msg;
        }
        return $test->isValid() ? "All validations passed" : "Checks could not be validated";
    }

    /**
     * Get expected error type
     *
     * @param TestUnit $test
     * @return string
     */
    public function getErrorType(TestUnit $test): string
    {
        if($test->isValid()) {
            return "";
        }
        return ($test->hasError() || $this->case->getHasError()) ? "error" : "failure";
    }

    /**
     * Get throwable from TestCase or TestUnit
     * @param TestUnit $test
     * @return ExceptionItem|null
     */
    public function getThrowable(TestUnit $test): ?ExceptionItem
    {
        if(!$this->case->getHasError() && !$test->hasError()) {
            return null;
        }
        if($this->case->getThrowable() !== null) {
            return $this->case->getThrowable();
        }
        return $test->getThrowable();
    }

    /**
     * Check if Error is a PHP error, if false and has error then the error is an unhandled exception error.
     *
     * @param TestUnit $test
     * @return bool
     */
    public function isPHPError(TestUnit $test): bool
    {
        $throwable = $this->getThrowable($test);
        return ($throwable !== null && $throwable->getException() instanceof BlunderErrorException);
    }

    /**
     * Returns true if an assert error as been triggered
     *
     * @return bool
     */
    public function hasAssertError(): bool
    {
        return $this->case->getThrowable() !== null && $this->case->getThrowable()->getException() instanceof AssertionError;
    }

    /**
     * Returns assert message if an assert error has been triggered
     *
     * @return string
     */
    public function getAssertMessage(): string
    {
        if($this->hasAssertError()) {
            return $this->case->getThrowable()->getException()->getMessage();
        }
        return "";
    }

    /**
     * Get class name
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return str_replace(['_', '-'], '.', basename($this->suitName, ".php"));
    }

    /**
     * Get error message
     *
     * @param TestUnit $test
     * @return string
     */
    public function getErrorMessage(TestUnit $test): string
    {
        $throwable = $this->getThrowable($test);
        if($throwable === null) {
            return "";
        }
        $cliErrorHandler = new CliHandler();
        return $cliErrorHandler->getErrorMessage($throwable);
    }

    /**
     * Get error message
     *
     * @param TestUnit $test
     * @return string
     */
    public function getSmallErrorMessage(TestUnit $test): string
    {
        $throwable = $this->getThrowable($test);
        if($throwable === null) {
            return "";
        }
        $cliErrorHandler = new CliHandler();
        return $cliErrorHandler->getSmallErrorMessage($throwable);
    }

    /**
     * Get message, will return an autogenerated message of validation errors
     *
     * @param TestUnit $test
     * @param TestItem $unit
     * @return string
     */
    public function getMessage(TestUnit $test, TestItem $unit): string
    {
        $lengthA = $test->getValidationLength();
        $validation = $unit->getValidationTitle();
        return "   " . str_pad($validation, $lengthA) . " → failed";
    }

    /**
     * @param TestItem $unit
     * @param string $failedMsg
     * @return string
     * @throws \ErrorException
     */
    public function getComparison(TestItem $unit, string $failedMsg): string
    {
        if ($unit->hasComparison()) {
            $compare = $unit->getComparison();
            $lengthB = (strlen($compare) + strlen($failedMsg) - 8);
            return  str_pad($compare, $lengthB, " ", STR_PAD_LEFT);
        }

        return "";
    }

    /**
     * Get input value as string
     *
     * Note: Will stringify every value into something more readable
     *
     * @param TestUnit $test
     * @return string
     * @throws \ErrorException
     */
    public function getValue(TestUnit $test): string
    {
        return Helpers::stringifyDataTypes($test->getValue());
    }

    /**
     * Make a file path into a title
     *
     * @param string $file
     * @param int $length
     * @param bool $removeSuffix
     * @return string
     */
    protected function formatFileTitle(string $file, int $length = 3, bool $removeSuffix = true): string
    {
        $file = explode("/", $file);
        if ($removeSuffix) {
            //$pop = array_pop($file);
            //$file[] = substr($pop, (int)strpos($pop, 'unitary') + 8);
        }
        $file = array_chunk(array_reverse($file), $length);
        $file = implode("\\", array_reverse($file[0]));
        //$exp = explode('.', $file);
        //$file = reset($exp);
        return ".." . $file;
    }
}
