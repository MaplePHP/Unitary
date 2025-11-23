<?php

/**
 * Unit — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

declare(strict_types=1);

namespace MaplePHP\Unitary;

use Closure;
use ErrorException;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\Exceptions\BlunderSilentException;
use Psr\Http\Message\StreamInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Config\TestConfig;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use RuntimeException;
use Throwable;

final class Unit
{
    private ?BodyInterface $handler;
    private int $index = 0;
    private array $cases = [];
    private bool $disableAllTests = false;
    private bool $executed = false;
    private string $file = "";
    private bool $showErrorsOnly = false;
    private bool $failFast = false;
    private ?string $show = null;
    private bool $verbose = false;
    private bool $alwaysShowFiles = false;
    private static int $totalPassedTests = 0;
    private static int $totalTests = 0;
    private static int $totalErrors = 0;
    private static int $totalSkippedTests = 0;
    private static float $totalMemory = 0;
    private static float $totalDuration = 0;

    /**
     * Initialize Unit test instance with optional handler
     *
     * @param BodyInterface|null $handler Optional handler for test execution
     */
    public function __construct(BodyInterface|null $handler = null)
    {
        $this->setHandler($handler);
    }

    /**
     * Get the PSR stream from the handler
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->handler->getBody();
    }

    /**
     * Set output handler
     *
     * @param BodyInterface|null $handler
     * @return $this
     */
    public function setHandler(BodyInterface|null $handler = null): self
    {
        $this->handler = ($handler === null) ? new CliRenderer(new Command()) : $handler;
        return $this;
    }

    /**
     * Will pass a test file name to script used to:
     * - Allocate tests
     * - Show where tests is executed
     *
     * @param string $file
     * @return $this
     */
    public function setFile(string $file): Unit
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Will exit script if errors is thrown
     *
     * @param bool $failFast
     * @return $this
     */
    public function setFailFast(bool $failFast): Unit
    {
        $this->failFast = $failFast;
        return $this;
    }

    /**
     * Will only display error and hide passed tests
     *
     * @param bool $showErrorsOnly
     * @return $this
     */
    public function setShowErrorsOnly(bool $showErrorsOnly): Unit
    {
        $this->showErrorsOnly = $showErrorsOnly;
        return $this;
    }

    /**
     * Display only one test -
     * Will accept either file checksum or name form named tests
     *
     * @param string|null $show
     * @return $this
     */
    public function setShow(?string $show = null): Unit
    {
        $this->show = $show;
        return $this;
    }

    /**
     * Show hidden messages
     *
     * @param bool $verbose
     * @return void
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * Show file paths even on passed tests
     *
     * @param bool $alwaysShowFiles
     * @return void
     */
    public function setAlwaysShowFiles(bool $alwaysShowFiles): void
    {
        $this->alwaysShowFiles = $alwaysShowFiles;
    }

    /**
     * This will pass over all relevant configurations to new Unit instances
     *
     * @param Unit $inst
     * @return $this
     */
    public function inheritConfigs(Unit $inst): Unit
    {
        foreach (get_object_vars($inst) as $prop => $value) {
            if($prop !== "index" && $prop !== "cases") {
                $this->$prop = $value;
            }
        }
        return $this;
    }

    /**
     * Check if all executed tests is successful
     *
     * @return bool
     */
    public static function isSuccessful(): bool
    {
        return (self::$totalPassedTests === self::$totalTests);
    }

    /**
     * Get number of executed passed tests
     *
     * @return int
     */
    public static function getPassedTests(): int
    {
        return self::$totalPassedTests;
    }

    /**
     * Get number of executed tests
     *
     * @return int
     */
    public static function getTotalTests(): int
    {
        return self::$totalTests;
    }

    /**
     * Get the total number of failed tests
     *
     * @return int
     */
    public static function getTotalFailed(): int
    {
        return self::$totalTests-self::$totalPassedTests;
    }

    /**
     * Get the total number of error
     *
     * NOTE: That an error is a PHP failure or a exception that has been thrown.
     *
     * @return int
     */
    public static function getTotalErrors(): int
    {
        return self::$totalErrors;
    }

    /**
     * Get the total number of skipped grouped test
     *
     * @return int
     */
    public static function getTotalSkipped(): int
    {
        return self::$totalSkippedTests;
    }

    /**
     * Increment error count
     *
     * @return void
     */
    public static function incrementErrors(): void
    {
        self::$totalErrors++;
    }

    /**
     * Get total duration of all tests
     *
     * @return float
     */
    public static function getTotalDuration(): float
    {
        return self::$totalDuration;
    }

    /**
     * Get total duration of all tests
     *
     * @return float
     */
    public static function getTotalMemory(): float
    {
        return self::$totalMemory;
    }

    /**
     * This will disable "ALL" tests in the test file
     * If you want to skip a specific test, use the TestConfig class instead
     *
     * @param bool $disable
     * @return void
     */
    public function disableAllTest(bool $disable): void
    {
        $this->disableAllTests = $disable;
    }

    /**
     * Name has been changed to case
     *
     * Note: This will become DEPRECATED in the future with exception
     *
     * @param string $message
     * @param Closure $callback
     * @return void
     */
    public function add(string $message, Closure $callback): void
    {
        $this->case($message, $callback);
    }

    /**
     * Adds a test case to the collection (group() is preferred over case())
     * The key difference from group() is that this TestCase will NOT be bound the Closure
     *
     * @param string|TestConfig $message The message or configuration for the test case.
     * @param Closure $expect The closure containing the test case logic.
     * @param TestConfig|null $config
     * @return void
     */
    public function group(string|TestConfig $message, Closure $expect, ?TestConfig $config = null): void
    {
        if ($config !== null && !$config->hasSubject()) {
            $addMessage = ($message instanceof TestConfig && $message->hasSubject()) ? $message->message : $message;
            $message = $config->withSubject($addMessage);
        }
        $this->addCase($message, $expect);
    }

    /**
     * Adds a test case to the collection.
     * The key difference from group() is that this TestCase will be bound the Closure
     * Not Deprecated but might be in the far future
     *
     * @param string|TestConfig $message The message or configuration for the test case.
     * @param Closure $callback The closure containing the test case logic.
     * @return void
     */
    public function case(string|TestConfig $message, Closure $callback): void
    {
        $this->addCase($message, $callback, true);
    }

    /**
     * Execute tests suite
     *
     * @return bool
     * @throws ErrorException
     * @throws BlunderErrorException
     * @throws Throwable
     */
    public function execute(): bool
    {
        if ($this->executed || $this->disableAllTests) {
            return false;
        }
        ob_start();
        //$countCases = count($this->cases);
        $handler = $this->handler;
        if (count($this->cases) === 0) {
            return false;
        }
        $fileChecksum = md5($this->file);
        foreach ($this->cases as $index => $row) {
            if (!($row instanceof TestCase)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestCase.");
            }

            $row->dispatchTest($row);
            $deferred = $row->runDeferredValidations();
            $checksum = $fileChecksum . $index;
            $show = ($row->getConfig()->select === $this->show || $this->show === $checksum);

            if (($this->show !== null) && !$show) {
                continue;
            }
            // Success, no need to try to show errors, continue with the next test
            if ($this->showErrorsOnly !== false && !$row->hasFailed()) {
                continue;
            }
            $handler->setCase($row);
            $handler->setSuitName($this->file);
            $handler->setChecksum($checksum);
            $handler->setTests($deferred);
            $handler->setShow($show);
            $handler->setVerbose($this->verbose);
            $handler->setAlwaysShowFiles($this->alwaysShowFiles);
            $handler->buildBody();

            if($row->getHasError()) {
                self::incrementErrors();
            }

            // Important to add test from skip as successfully count to make sure that
            // the total passed tests are correct, and it will not exit with code 1
            self::$totalPassedTests += ($row->getConfig()->skip) ? $row->getTotal() : $row->getCount();
            self::$totalSkippedTests += $row->getSkipped();
            self::$totalTests += $row->getTotal();
            self::$totalMemory += $row->getMemory();
            self::$totalDuration += $row->getDuration();
        }
        $out = $handler->outputBuffer();
        if ($out) {
            $handler->buildNotes();
        }
        $this->executed = true;
        return true;
    }

    /**
     * Validate method that must be called within a group method
     *
     * @return self
     * @throws RuntimeException When called outside a group method
     */
    public function validate(): self
    {
        throw new RuntimeException("The validate() method must be called inside a group() method! " .
            "Move this validate() call inside your group() callback function.");
    }

    /**
     * Validate method that must be called within a group method
     *
     * @return self
     * @throws RuntimeException When called outside a group method
     */
    public function assert(): self
    {
        throw new RuntimeException("The assert() method must be called inside a group() method! " .
            "Move this assert() call inside your group() callback function.");
    }

    /**
     * Adds a test case to the collection.
     *
     * @param string|TestConfig $message The description or configuration of the test case.
     * @param Closure $expect The closure that defines the test case logic.
     * @param bool $bindToClosure Indicates whether the closure should be bound to TestCase.
     * @return void
     */
    protected function addCase(string|TestConfig $message, Closure $expect, bool $bindToClosure = false): void
    {
        $testCase = new TestCase($message);
        $testCase->setFailFast($this->failFast);
        $testCase->bind($expect, $bindToClosure);
        $this->cases[$this->index] = $testCase;
        $this->index++;
    }

    // Deprecated: Almost same as `disableAllTest`, for older versions
    public function skip(bool $disable): self
    {
        $this->disableAllTests = $disable;
        return $this;
    }

    /**
     * DEPRECATED: Use TestConfig::setSelect instead
     * See documentation for more information
     *
     * @return void
     */
    public function manual(): void
    {
        throw new RuntimeException("Manual method has been deprecated, use TestConfig::setSelect instead. " .
            "See documentation for more information.");
    }

    /**
     * DEPRECATED: Not used anymore
     *
     * @return $this
     */
    public function addTitle(): self
    {
        return $this;
    }
}
