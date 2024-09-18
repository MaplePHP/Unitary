<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

use Exception;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Prompts\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Unit
{
    const PATTERN = 'unitary-*.php';
    private Command $command;
    private bool $quite;
    private ?string $title = null;
    private string $output = "";
    private array $args = [];
    private array $units;
    private int $index = 0;
    private array $error;
    private static string $file = "";
    private static array $info = [];

    public function __construct(bool $quite = false)
    {
        $this->command = new Command();
        $this->quite = $quite;
    }

    /**
     * Add title to the test (optional)
     * @param string $title
     * @return void
     */
    public function addTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Pass arguments to the testing script (optional)
     * @param array $args
     * @return void
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    /**
     * Get passed arguments in the testing script
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Add a test unit/group
     * @param string $message
     * @param callable $callback
     * @return void
     */
    public function add(string $message, callable $callback): void
    {
        $hasError = false;
        $test = $this->unitTest();
        // Make the tests
        ob_start();
        $callback($test);
        $this->output .= ob_get_clean();
        // Get the tests results
        $data = $test->getTestResult();
        $index = $this->index-1;

        $count = count($test->getTestCases());
        $this->error['info'] = [
            'count' => $count,
            'total' => $count
        ];
        $this->error['feed'][$index] = [
            'message' => $message,
            'file' => self::$file,
            'error' => []
        ];

        foreach($data as $key => $row) {
            if(!$row['test']) {
                $this->error['feed'][$index]['error'][$key] = $row;
                $this->error['info']['count']--;
            }
        }
    }

    /**
     * Access command instance
     * @return Command
     */
    public function command(): Command
    {
        return $this->command;
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
     * Execute tests suite
     * @return void
     */
    public function execute(): void
    {
        $run = new Run(new CliHandler());
        $run->load();



        $this->command->message("");

        foreach($this->error['feed'] as $error) {

            $color = ($error['error'] ? "red" : "blue");
            $flag = $this->command->getAnsi()->style(['blueBg', 'white'],  " PASS ");
            if($error['error']) {
                $flag = $this->command->getAnsi()->style(['redBg', 'white'],  " FAIL ");
            }

            $this->command->message("");
            $this->command->message(
                $flag .
                " " .
                $this->command->getAnsi()->style(["bold"],  $this->formatFileTitle($error['file'])) .
                " - " .
                $this->command->getAnsi()->style(["bold", $color],  $error['message'])
            );
            foreach($error['error'] as $row) {
                $this->command->message("");

                $this->command->message($this->command->getAnsi()->style(["bold", "red"], "Error: {$row['message']}"));
                $this->command->message($this->command->getAnsi()->bold("Value: ") . "{$row['readableValue']}");
                if(is_string($row['method'])) {
                    $this->command->message($this->command->getAnsi()->bold("Validation: ") . "{$row['method']}");
                }

            }

            $this->command->message("");
            $this->command->message(
                $this->command->getAnsi()->bold("Passed: ") .
                $this->command->getAnsi()->style([$color, "bold"], $this->error['info']['count'] . "/" . $this->error['info']['total']));
        }




        if($this->output) {
            $this->command->message($this->output);
        }
    }

    /**
     * Will Execute all unitary test files.
     * @param string $directory
     * @return void
     * @throws Exception
     */
    public function executeAll(string $directory): void
    {
        $files = $this->findFiles($directory);
        if (empty($files)) {
            throw new Exception("No files found matching the pattern \"" . static::PATTERN . "\" in directory \"$directory\" ");
        } else {
            foreach ($files as $file) {
                extract($this->args, EXTR_PREFIX_SAME, "wddx");
                self::$file = $file;
                require_once ($file);
            }
        }
    }

    /**
     * Init immutable validation test instance
     * @return Test
     */
    protected function unitTest(): Test
    {
        $test = $this->units[$this->index] = new Test();
        $this->index++;
        return $test;
    }

    /**
     * Will Scan and find all unitary test files
     * @param $dir
     * @return array
     */
    private function findFiles($dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (fnmatch(static::PATTERN, $file->getFilename()) &&
                (isset($this->args['path']) || !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR ))) {
                if(!$this->findExcluded($this->exclude(), $dir, $file->getPathname())) {
                    $files[] = $file->getPathname();
                }
            }
        }
        return $files;
    }

    /**
     * Get exclude parameter
     * @return array
     */
    function exclude(): array
    {
        $excl = array();
        if(isset($this->args['exclude'])) {
            $exclude = explode(',', $this->args['exclude']);
            foreach ($exclude as $file) {
                $file = str_replace(['"', "'"], "", $file);
                $new = trim($file);
                $lastChar = substr($new, -1);
                if($lastChar === DIRECTORY_SEPARATOR) {
                    $new .= "*";
                }
                $excl[] = trim($new);
            }
        }
        return $excl;
    }

    /**
     * Validate a exclude path
     * @param array $exclArr
     * @param string $relativeDir
     * @param string $file
     * @return bool
     */
    function findExcluded(array $exclArr, string $relativeDir, string $file): bool
    {
        $file = $this->getNaturalPath($file);
        foreach ($exclArr as $excl) {
            $relativeExclPath = $this->getNaturalPath($relativeDir . DIRECTORY_SEPARATOR . $excl);
            if(fnmatch($relativeExclPath, $file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get path as natural path
     * @param string $path
     * @return string
     */
    function getNaturalPath(string $path): string
    {
        return str_replace("\\", "/", $path);
    }
    /**
     * Get path as natural path
     * @param string $path
     * @return string
     */
    function getUnnaturalPath(string $path): string
    {
        return str_replace("/", "\\", $path);
    }

    /**
     * Make file path into a title
     * @param string $file
     * @return string
     */
    function formatFileTitle(string $file): string
    {
        $file = explode("/", $file);
        $file = array_chunk(array_reverse($file), 3);
        $file = implode("\\", array_reverse($file[0]));
        $exp = explode('.', $file);
        return ".." . reset($exp);
    }
}