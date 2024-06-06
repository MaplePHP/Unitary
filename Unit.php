<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

use Exception;
use MaplePHP\Prompts\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Unit
{
    const PATTERN = 'unitary-*.php';
    private Command $command;
    private bool $quite;
    private ?string $title = null;
    private array $args = [];
    private array $units;
    private int $index = 0;
    private array $error;

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
        $callback($test);
        // Get the tests results
        $data = $test->getTestResult();
        $index = $this->index-1;

        $this->error[$index] = [
            'message' => $message
        ];
        foreach($data as $key => $row) {
            if(!$row['test']) {
                $hasError = true;
                $this->error[$index]['error'][$key] = $row;
            }
        }
        if(!$hasError) {
            unset($this->error[$index]);
        }
    }

    /**
     * Execute tests suite
     * @return void
     */
    public function execute(): void
    {
        if(!is_null($this->title) && (!$this->quite || count($this->error) > 0)) {
            $this->command->title("\n--------- $this->title ---------");
        }
        if(count($this->error) > 0) {
            foreach($this->error as $error) {
                $tests = [];
                $this->command->title("\n{$error['message']}");
                foreach($error['error'] as $row) {
                    $tests[] = $row['method'];
                    $this->command->error("Test-value {$row['readableValue']}");
                    $this->command->error("{$row['message']}\n");
                }
            }

        } elseif(!$this->quite) {
            $this->command->approve("Every test has been successfully run!");
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
                (isset($this->args['path']) || !str_contains($file->getPathname(), "vendor/"))) {
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
                $new = trim($file);
                $lastChar = substr($new, -1);
                if($lastChar === "/") {
                    $new .= "*";
                }
                $excl[] = trim($new);
            }
        }
        return $excl;
    }

    function findExcluded(array $exclArr, string $relativeDir, string $file): bool
    {
        foreach ($exclArr as $excl) {
            if(fnmatch($relativeDir . "/". $excl, $file)) {
               return true;
            }
        }
        return false;
    }
}
