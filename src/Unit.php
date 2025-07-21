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
use Throwable;
use MaplePHP\Unitary\Handlers\CliHandler;
use MaplePHP\Unitary\TestUtils\Configs;
use RuntimeException;
use MaplePHP\Blunder\BlunderErrorException;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Handlers\HandlerInterface;
use MaplePHP\Unitary\Utils\Performance;

final class Unit
{
    private ?HandlerInterface $handler = null;
    private Command $command;
    private string $output = "";
    private int $index = 0;
    private array $cases = [];
    private bool $disableAllTests = false;
    private bool $executed = false;
    private static array $headers = [];
    private static ?Unit $current;
    public static int $totalPassedTests = 0;
    public static int $totalTests = 0;

    /**
     * Initialize Unit test instance with optional handler
     *
     * @param HandlerInterface|StreamInterface|null $handler Optional handler for test execution
     *        If HandlerInterface is provided, uses its command
     *        If StreamInterface is provided, creates a new Command with it
     *        If null, creates a new Command without a stream
     */
    public function __construct(HandlerInterface|StreamInterface|null $handler = null)
    {
        if ($handler instanceof HandlerInterface) {
            $this->handler = $handler;
            $this->command = $this->handler->getCommand();
        } else {
            $this->command = ($handler === null) ? Configs::getInstance()->getCommand() : new Command($handler);
        }
        self::$current = $this;
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
     * Access command instance
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * Access command instance
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        return $this->command->getStream();
    }

    /**
     * Disable ANSI
     * @param bool $disableAnsi
     * @return self
     */
    public function disableAnsi(bool $disableAnsi): self
    {
        $this->command->getAnsi()->disableAnsi($disableAnsi);
        return $this;
    }

    /**
     * Print message
     * @param string $message
     * @return false|string
     */
    public function message(string $message): false|string
    {
        return $this->command->message($message);
    }

    /**
     * Confirm for execute
     *
     * @param string $message
     * @return bool
     */
    public function confirm(string $message = "Do you wish to continue?"): bool
    {
        return $this->command->confirm($message);
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
     * @param Closure $callback The closure containing the test case logic.
     * @return void
     */
    public function group(string|TestConfig $message, Closure $callback): void
    {
        $this->addCase($message, $callback);
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

        // LOOP through each case
        ob_start();
        //$countCases = count($this->cases);

        $handler = new CliHandler($this->command);

        foreach ($this->cases as $index => $row) {
            if (!($row instanceof TestCase)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestCase.");
            }

            $errArg = self::getArgs("errors-only");
            $row->dispatchTest($row);
            $tests = $row->runDeferredValidations();
            $checksum = (string)(self::$headers['checksum'] ?? "") . $index;

            $show = ($row->getConfig()->select === self::getArgs('show') || self::getArgs('show') === $checksum);
            if((self::getArgs('show') !== false) && !$show) {
                continue;
            }

            // Success, no need to try to show errors, continue with the next test
            if ($errArg !== false && !$row->hasFailed()) {
                continue;
            }

            $handler->setCase($row);
            $handler->setSuitName(self::$headers['file'] ?? "");
            $handler->setChecksum($checksum);
            $handler->setTests($tests);
            $handler->setShow($show);
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
     * This is custom header information that is passed, that work with both CLI and Browsers
     *
     * @param array $headers
     * @return void
     */
    public static function setHeaders(array $headers): void
    {
        self::$headers = $headers;
    }

    /**
     * Get passed CLI arguments
     *
     * @param string $key
     * @return mixed
     */
    public static function getArgs(string $key): mixed
    {
        return (self::$headers['args'][$key] ?? false);
    }

    /**
     * The test is liner it also has a current test instance that needs
     * to be rested when working with loop
     *
     * @return void
     */
    public static function resetUnit(): void
    {
        self::$current = null;
    }

    /**
     * Check if a current instance exists
     *
     * @return bool
     */
    public static function hasUnit(): bool
    {
        return self::$current !== null;
    }

    /**
     * Get the current instance
     *
     * @return ?Unit
     */
    public static function getUnit(): ?Unit
    {
        $verbose = self::getArgs('verbose');
        if ($verbose !== false && self::hasUnit() === false) {
            $file = self::$headers['file'] ?? "";

            $command = new Command();
            $command->message(
                $command->getAnsi()->style(['redBg', 'brightWhite'], " ERROR ") . ' ' .
                $command->getAnsi()->style(['red', 'bold'], "The Unit instance is missing in the file")
            );
            $command->message('');
            $command->message($command->getAnsi()->bold("In File:"));
            $command->message($file);
            $command->message('');
        }
        return self::$current;
    }

    /**
     * This will be called when every test has been run by the FileIterator
     * @return void
     */
    public static function completed(): void
    {
        if (self::$current !== null && self::$current->handler === null) {
            $dot = self::$current->command->getAnsi()->middot();

            //self::$current->command->message("");
            self::$current->command->message(
                self::$current->command->getAnsi()->style(
                    ["italic", "grey"],
                    "Total: " . self::$totalPassedTests . "/" . self::$totalTests . " $dot " .
                    "Peak memory usage: " . (string)round(memory_get_peak_usage() / 1024, 2) . " KB"
                )
            );
            self::$current->command->message("");
        }
    }

    /**
     * Check if all tests is successful
     *
     * @return bool
     */
    public static function isSuccessful(): bool
    {
        return (self::$totalPassedTests === self::$totalTests);
    }

    /**
     * Adds a test case to the collection.
     *
     * @param string|TestConfig $message The description or configuration of the test case.
     * @param Closure $callback The closure that defines the test case logic.
     * @param bool $bindToClosure Indicates whether the closure should be bound to TestCase.
     * @return void
     */
    protected function addCase(string|TestConfig $message, Closure $callback, bool $bindToClosure = false): void
    {
        $testCase = new TestCase($message);
        $testCase->bind($callback, $bindToClosure);
        $this->cases[$this->index] = $testCase;
        $this->index++;
    }


    // NOTE: Just a test will be added in a new library and controller.
    public function performance(Closure $func, ?string $title = null): void
    {
        $start = new Performance();
        $func = $func->bindTo($this);
        if ($func !== null) {
            $func($this);
        }
        $line = $this->command->getAnsi()->line(80);
        $this->command->message("");
        $this->command->message($this->command->getAnsi()->style(["bold", "yellow"], "Performance" . ($title !== null ? " - $title:" : ":")));

        $this->command->message($line);
        $this->command->message(
            $this->command->getAnsi()->style(["bold"], "Execution time: ") .
            ((string)round($start->getExecutionTime(), 3) . " seconds")
        );
        $this->command->message(
            $this->command->getAnsi()->style(["bold"], "Memory Usage: ") .
            ((string)round($start->getMemoryUsage(), 2) . " KB")
        );
        /*
         $this->command->message(
            $this->command->getAnsi()->style(["bold", "grey"], "Peak Memory Usage: ") .
            $this->command->getAnsi()->blue(round($start->getMemoryPeakUsage(), 2) . " KB")
        );
         */
        $this->command->message($line);
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
     * DEPRECATED: Append to global header
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function appendHeader(string $key, mixed $value): void
    {
        self::$headers[$key] = $value;
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

