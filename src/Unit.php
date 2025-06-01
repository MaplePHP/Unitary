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
use Exception;
use MaplePHP\Blunder\BlunderErrorException;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Handlers\HandlerInterface;
use MaplePHP\Unitary\Utils\Helpers;
use MaplePHP\Unitary\Utils\Performance;
use RuntimeException;
use Throwable;

final class Unit
{
    private ?HandlerInterface $handler = null;
    private Command $command;
    private string $output = "";
    private int $index = 0;
    private array $cases = [];
    private bool $skip = false;
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
            $this->command = new Command($handler);
        }
        self::$current = $this;
    }

    /**
     * This will skip "ALL" tests in the test file
     * If you want to skip a specific test, use the TestConfig class instead
     *
     * @param bool $skip
     * @return $this
     */
    public function skip(bool $skip): self
    {
        $this->skip = $skip;
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
     * confirm for execute
     * @param string $message
     * @return bool
     */
    public function confirm(string $message = "Do you wish to continue?"): bool
    {
        return $this->command->confirm($message);
    }

    /**
     * Name has been changed to case
     * WILL BECOME DEPRECATED VERY SOON
     * @param string $message
     * @param Closure $callback
     * @return void
     */
    public function add(string $message, Closure $callback): void
    {
        //trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
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
        $this->template();
        $this->help();
        if ($this->executed || $this->skip) {
            return false;
        }

        // LOOP through each case
        ob_start();
        //$countCases = count($this->cases);
        foreach ($this->cases as $index => $row) {
            if (!($row instanceof TestCase)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestCase.");
            }

            $errArg = self::getArgs("errors-only");
            $row->dispatchTest($row);
            $tests = $row->runDeferredValidations();
            $checksum = (string)(self::$headers['checksum'] ?? "") . $index;
            $color = ($row->hasFailed() ? "brightRed" : "brightBlue");
            $flag = $this->command->getAnsi()->style(['blueBg', 'brightWhite'], " PASS ");
            if ($row->hasFailed()) {
                $flag = $this->command->getAnsi()->style(['redBg', 'brightWhite'], " FAIL ");
            }
            if ($row->getConfig()->skip) {
                $color = "yellow";
                $flag = $this->command->getAnsi()->style(['yellowBg', 'black'], " SKIP ");
            }

            $show = ($row->getConfig()->select === self::getArgs('show') || self::getArgs('show') === $checksum);
            if((self::getArgs('show') !== false) && !$show) {
                continue;
            }

            // Success, no need to try to show errors, continue with the next test
            if ($errArg !== false && !$row->hasFailed()) {
                continue;
            }

            $this->command->message("");
            $this->command->message(
                $flag . " " .
                $this->command->getAnsi()->style(["bold"], $this->formatFileTitle((string)(self::$headers['file'] ?? ""))) .
                " - " .
                $this->command->getAnsi()->style(["bold", $color], (string)$row->getMessage())
            );
            if($show && !$row->hasFailed()) {
                $this->command->message("");
                $this->command->message(
                    $this->command->getAnsi()->style(["italic", $color], "Test file: " . (string)self::$headers['file'])
                );
            }

            if (($show || !$row->getConfig()->skip)) {
                foreach ($tests as $test) {
                    if (!($test instanceof TestUnit)) {
                        throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestUnit.");
                    }

                    if (!$test->isValid()) {
                        $msg = (string)$test->getMessage();
                        $this->command->message("");
                        $this->command->message(
                            $this->command->getAnsi()->style(["bold", $color], "Error: ") .
                            $this->command->getAnsi()->bold($msg)
                        );
                        $this->command->message("");

                        $trace = $test->getCodeLine();
                        if (!empty($trace['code'])) {
                            $this->command->message($this->command->getAnsi()->style(["bold", "grey"], "Failed on {$trace['file']}:{$trace['line']}"));
                            $this->command->message($this->command->getAnsi()->style(["grey"], " → {$trace['code']}"));
                        }

                        foreach ($test->getUnits() as $unit) {

                            /** @var TestItem $unit */
                            if (!$unit->isValid()) {
                                $lengthA = $test->getValidationLength();
                                $validation = $unit->getValidationTitle();
                                $title = str_pad($validation, $lengthA);
                                $compare = $unit->hasComparison() ? $unit->getComparison() : "";

                                $failedMsg = "   " .$title . " → failed";
                                $this->command->message($this->command->getAnsi()->style($color, $failedMsg));

                                if ($compare) {
                                    $lengthB = (strlen($compare) + strlen($failedMsg) - 8);
                                    $comparePad = str_pad($compare, $lengthB, " ", STR_PAD_LEFT);
                                    $this->command->message(
                                        $this->command->getAnsi()->style($color, $comparePad)
                                    );
                                }
                            }
                        }
                        if ($test->hasValue()) {
                            $this->command->message("");
                            $this->command->message(
                                $this->command->getAnsi()->bold("Input value: ") .
                                Helpers::stringifyDataTypes($test->getValue())
                            );
                        }
                    }
                }
            }

            self::$totalPassedTests += $row->getCount();
            self::$totalTests += $row->getTotal();
            if ($row->getConfig()->select) {
                $checksum .= " (" . $row->getConfig()->select . ")";
            }
            $this->command->message("");

            $passed = $this->command->getAnsi()->bold("Passed: ");
            if ($row->getHasAssertError()) {
                $passed .= $this->command->getAnsi()->style(["grey"], "N/A");
            } else {
                $passed .= $this->command->getAnsi()->style([$color], $row->getCount() . "/" . $row->getTotal());
            }

            $footer = $passed .
                $this->command->getAnsi()->style(["italic", "grey"], " - ". $checksum);
            if (!$show && $row->getConfig()->skip) {
                $footer = $this->command->getAnsi()->style(["italic", "grey"], $checksum);
            }
            $this->command->message($footer);
            $this->command->message("");
        }
        $this->output .= (string)ob_get_clean();

        if ($this->output) {
            $this->buildNotice("Note:", $this->output, 80);
        }
        $this->handler?->execute();
        $this->executed = true;
        return true;
    }

    /**
     * Will reset the executing and stream if is a seekable stream
     *
     * @return bool
     */
    public function resetExecute(): bool
    {
        if ($this->executed) {
            if ($this->getStream()->isSeekable()) {
                $this->getStream()->rewind();
            }
            $this->executed = false;
            return true;
        }
        return false;
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
     * Build the notification stream
     * @param string $title
     * @param string $output
     * @param int $lineLength
     * @return void
     */
    public function buildNotice(string $title, string $output, int $lineLength): void
    {
        $this->output = wordwrap($output, $lineLength);
        $line = $this->command->getAnsi()->line($lineLength);

        $this->command->message("");
        $this->command->message($this->command->getAnsi()->style(["bold"], $title));
        $this->command->message($line);
        $this->command->message($this->output);
        $this->command->message($line);
    }

    /**
     * Make a file path into a title
     * @param string $file
     * @param int $length
     * @param bool $removeSuffix
     * @return string
     */
    private function formatFileTitle(string $file, int $length = 3, bool $removeSuffix = true): string
    {
        $file = explode("/", $file);
        if ($removeSuffix) {
            $pop = array_pop($file);
            $file[] = substr($pop, (int)strpos($pop, 'unitary') + 8);
        }
        $file = array_chunk(array_reverse($file), $length);
        $file = implode("\\", array_reverse($file[0]));
        //$exp = explode('.', $file);
        //$file = reset($exp);
        return ".." . $file;
    }

    /**
     * Global header information
     * @param array $headers
     * @return void
     */
    public static function setHeaders(array $headers): void
    {
        self::$headers = $headers;
    }

    /**
     * Get global header
     * @param string $key
     * @return mixed
     */
    public static function getArgs(string $key): mixed
    {
        return (self::$headers['args'][$key] ?? false);
    }

    /**
     * Append to global header
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function appendHeader(string $key, mixed $value): void
    {
        self::$headers[$key] = $value;
    }

    /**
     * Used to reset the current instance
     * @return void
     */
    public static function resetUnit(): void
    {
        self::$current = null;
    }

    /**
     * Used to check if an instance is set
     * @return bool
     */
    public static function hasUnit(): bool
    {
        return self::$current !== null;
    }

    /**
     * Used to get instance
     * @return ?Unit
     * @throws Exception
     */
    public static function getUnit(): ?Unit
    {
        if (self::hasUnit() === false) {
            throw new Exception("Unit has not been set yet. It needs to be set first.");
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
     * Check if successful
     * @return bool
     */
    public static function isSuccessful(): bool
    {
        return (self::$totalPassedTests !== self::$totalTests);
    }

    /**
     * Display a template for the Unitary testing tool
     * Shows a basic template for the Unitary testing tool
     * Only displays if --template argument is provided
     *
     * @return void
     */
    private function template(): void
    {
        if (self::getArgs("template") !== false) {

            $blocks = new Blocks($this->command);
            $blocks->addHeadline("\n--- Unitary template ---");
            $blocks->addCode(
                <<<'PHP'
                use MaplePHP\Unitary\{Unit, TestCase, TestConfig, Expect};
                
                $unit = new Unit();
                $unit->group("Your test subject", function (TestCase $case) {
                
                    $case->validate("Your test value", function(Expect $valid) {
                        $valid->isString();
                    });
                    
                });
                PHP
            );
            exit(0);
        }
    }

    /**
     * Display help information for the Unitary testing tool
     * Shows usage instructions, available options and examples
     * Only displays if --help argument is provided
     *
     * @return void True if help was displayed, false otherwise
     */
    private function help(): void
    {
        if (self::getArgs("help") !== false) {

            $blocks = new Blocks($this->command);
            $blocks->addHeadline("\n--- Unitary Help ---");
            $blocks->addSection("Usage", "php vendor/bin/unitary [options]");

            $blocks->addSection("Options", function(Blocks $inst) {
                return $inst
                    ->addOption("help", "Show this help message")
                    ->addOption("show=<hash|name>", "Run a specific test by hash or manual test name")
                    ->addOption("errors-only", "Show only failing tests and skip passed test output")
                    ->addOption("template", "Will give you a boilerplate test code")
                    ->addOption("path=<path>", "Specify test path (absolute or relative)")
                    ->addOption("exclude=<patterns>", "Exclude files or directories (comma-separated, relative to --path)");
            });

            $blocks->addSection("Examples", function(Blocks $inst) {
                return $inst
                    ->addExamples(
                        "php vendor/bin/unitary",
                        "Run all tests in the default path (./tests)"
                    )->addExamples(
                        "php vendor/bin/unitary --show=b0620ca8ef6ea7598eaed56a530b1983",
                        "Run the test with a specific hash ID"
                    )->addExamples(
                        "php vendor/bin/unitary --errors-only",
                        "Run all tests in the default path (./tests)"
                    )->addExamples(
                        "php vendor/bin/unitary --show=YourNameHere",
                        "Run a manually named test case"
                    )->addExamples(
                        "php vendor/bin/unitary --template",
                        "Run a and will give you template code for a new test"
                    )->addExamples(
                        'php vendor/bin/unitary --path="tests/" --exclude="tests/legacy/*,*/extras/*"',
                        'Run all tests under "tests/" excluding specified directories'
                    );
            });
            exit(0);
        }
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

    /**
     * DEPRECATED: Not used anymore
     * @return $this
     */
    public function addTitle(): self
    {
        return $this;
    }
}
