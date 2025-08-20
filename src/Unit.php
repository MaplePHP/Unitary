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
    private ?string $show = null;
    private bool $verbose = false;
    private bool $alwaysShowFiles = false;
    private static int $totalPassedTests = 0;
    private static int $totalTests = 0;

    /**
     * Initialize Unit test instance with optional handler
     *
     * @param BodyInterface|null $handler Optional handler for test execution
     */
    public function __construct(BodyInterface|null $handler = null)
    {
        $this->handler = ($handler === null) ? new CliRenderer(new Command()) : $handler;
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
     * This will help pass over some default for custom Unit instances
     *
     * @param Unit $inst
     * @return $this
     */
    public function inheritConfigs(Unit $inst): Unit
    {
        $this->setFile($inst->file);
        $this->setShow($inst->show);
        $this->setShowErrorsOnly($inst->showErrorsOnly);
        $this->setVerbose($inst->verbose);
        $this->setAlwaysShowFiles($inst->alwaysShowFiles);
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
        if($config !== null && !$config->hasSubject()) {
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
        if(count($this->cases) === 0) {
            return false;
        }

        $fileChecksum = md5($this->file);
        foreach ($this->cases as $index => $row) {
            if (!($row instanceof TestCase)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestCase.");
            }
            $row->dispatchTest($row);
            $tests = $row->runDeferredValidations();
            $checksum = $fileChecksum . $index;
            $show = ($row->getConfig()->select === $this->show || $this->show === $checksum);

            if(($this->show !== null) && !$show) {
                continue;
            }
            // Success, no need to try to show errors, continue with the next test
            if ($this->showErrorsOnly !== false && !$row->hasFailed()) {
                continue;
            }
            $handler->setCase($row);
            $handler->setSuitName($this->file);
            $handler->setChecksum($checksum);
            $handler->setTests($tests);
            $handler->setShow($show);
            $handler->setVerbose($this->verbose);
            $handler->setAlwaysShowFiles($this->alwaysShowFiles);
            $handler->buildBody();

            // Important to add test from skip as successfully count to make sure that
            // the total passed tests are correct, and it will not exit with code 1
            self::$totalPassedTests += ($row->getConfig()->skip) ? $row->getTotal() : $row->getCount();
            self::$totalTests += $row->getTotal();
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

