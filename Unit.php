<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

use Closure;
use ErrorException;
use Exception;
use RuntimeException;
use MaplePHP\Unitary\Handlers\HandlerInterface;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Prompts\Command;
use Throwable;

class Unit
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
    private static array $manual = [];
    public static int $totalPassedTests = 0;
    public static int $totalTests = 0;

    public function __construct(HandlerInterface|StreamInterface|null $handler = null)
    {
        if($handler instanceof HandlerInterface) {
            $this->handler = $handler;
            $this->command = $this->handler->getCommand();
        } else {
            $this->command = new Command($handler);
        }
        self::$current = $this;
    }

    /**
     * Skip you can add this if you want to turn of validation of a unit case
     * @param bool $skip
     * @return $this
     */
    public function skip(bool $skip): self
    {
        $this->skip = $skip;
        return $this;
    }

    /**
     * Make script manually callable
     * @param string $key
     * @return $this
     */
    public function manual(string $key): self
    {
        if(isset(self::$manual[$key])) {
            $file = (string)(self::$headers['file'] ?? "none");
            throw new RuntimeException("The manual key \"$key\" already exists.
                Please set a unique key in the " . $file. " file.");
        }
        self::$manual[$key] = self::$headers['checksum'];
        return $this->skip(true);
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
     * DEPRECATED: Name has been changed to case
     * @param string $message
     * @param Closure $callback
     * @return void
     */
    public function add(string $message, Closure $callback): void
    {
        // Might be trigger in future
        //trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        $this->case($message, $callback);
    }

    /**
     * Add a test unit/group
     * @param string $message
     * @param Closure $callback
     * @return void
     */
    public function case(string $message, Closure $callback): void
    {
        $testCase = new TestCase($message);
        $testCase->bind($callback);
        $this->cases[$this->index] = $testCase;
        $this->index++;
    }

    /**
     * Execute tests suite
     * @return bool
     * @throws ErrorException
     */
    public function execute(): bool
    {
        if($this->executed || !$this->validate()) {
            return false;
        }

        // LOOP through each case
        ob_start();
        foreach($this->cases as $row) {

            if(!($row instanceof TestCase)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestCase.");
            }

            try {
                $tests = $row->dispatchTest();
            } catch (Throwable $e) {
                $file = $this->formatFileTitle((string)(self::$headers['file'] ?? ""), 5, false);
                throw new RuntimeException($e->getMessage() . ". Error originated from: ". $file, (int)$e->getCode(), $e);
            }

            $color = ($row->hasFailed() ? "brightRed" : "brightBlue");
            $flag = $this->command->getAnsi()->style(['blueBg', 'brightWhite'], " PASS ");
            if($row->hasFailed()) {
                $flag = $this->command->getAnsi()->style(['redBg', 'brightWhite'], " FAIL ");
            }

            $this->command->message("");
            $this->command->message(
                $flag . " " .
                $this->command->getAnsi()->style(["bold"], $this->formatFileTitle((string)(self::$headers['file'] ?? ""))) .
                " - " .
                $this->command->getAnsi()->style(["bold", $color], (string)$row->getMessage())
            );

            foreach($tests as $test) {
                if(!($test instanceof TestUnit)) {
                    throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestUnit.");
                }

                if(!$test->isValid()) {
                    $msg = (string)$test->getMessage();
                    $this->command->message("");
                    $this->command->message($this->command->getAnsi()->style(["bold", "brightRed"], "Error: " . $msg));
                    /** @var array<string, string> $unit */
                    foreach($test->getUnits() as $unit) {
                        $this->command->message(
                            $this->command->getAnsi()->bold("Validation: ") .
                            $this->command->getAnsi()->style(
                                ((!$unit['valid']) ? "brightRed" : null),
                                $unit['validation'] . ((!$unit['valid']) ? " (fail)" : "")
                            )
                        );
                    }
                    $this->command->message($this->command->getAnsi()->bold("Value: ") . $test->getReadValue());
                }
            }

            self::$totalPassedTests += $row->getCount();
            self::$totalTests += $row->getTotal();

            $checksum = (string)(self::$headers['checksum'] ?? "");
            $this->command->message("");

            $this->command->message(
                $this->command->getAnsi()->bold("Passed: ") .
                $this->command->getAnsi()->style([$color], $row->getCount() . "/" . $row->getTotal()) .
                $this->command->getAnsi()->style(["italic", "grey"], " - ". $checksum)
            );
        }
        $this->output .= ob_get_clean();

        if($this->output) {
            $this->buildNotice("Note:", $this->output, 80);
        }
        if(!is_null($this->handler)) {
            $this->handler->execute();
        }
        $this->executed = true;
        return true;
    }

    /**
     * Will reset the execute and stream if is a seekable stream.
     * @return bool
     */
    public function resetExecute(): bool
    {
        if($this->executed) {
            if($this->getStream()->isSeekable()) {
                $this->getStream()->rewind();
            }
            $this->executed = false;
            return true;
        }
        return false;
    }

    /**
     * Validate before execute test
     * @return bool
     */
    private function validate(): bool
    {
        $args = (array)(self::$headers['args'] ?? []);
        $manual = isset($args['show']) ? (string)$args['show'] : "";
        if(isset($args['show'])) {
            return !((self::$manual[$manual] ?? "") !== self::$headers['checksum'] && $manual !== self::$headers['checksum']);
        }
        if($this->skip) {
            return false;
        }
        return true;
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
     * Make file path into a title
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
        $exp = explode('.', $file);
        $file = reset($exp);
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
     * Used to reset current instance
     * @return void
     */
    public static function resetUnit(): void
    {
        self::$current = null;
    }

    /**
     * Used to check if instance is set
     * @return bool
     */
    public static function hasUnit(): bool
    {
        return !is_null(self::$current);
    }

    /**
     * Used to get instance
     * @return ?Unit
     * @throws Exception
     */
    public static function getUnit(): ?Unit
    {
        if(is_null(self::hasUnit())) {
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
        if(!is_null(self::$current) && is_null(self::$current->handler)) {
            self::$current->command->message("");
            self::$current->command->message(
                self::$current->command->getAnsi()->style(
                    ["italic", "grey"],
                    "Total: " . self::$totalPassedTests . "/" . self::$totalTests
                )
            );
        }
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
