<?php
/**
 * FileIterator — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Discovery;

use Closure;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class TestDiscovery
{
    public const PATTERN = 'unitary-*.php';

    private array $args;
    private bool $verbose = false;
    private bool $smartSearch = false;
    private ?string $exclude = null;

    private ?Command $command = null;
    private static ?Unit $unitary = null;


    public function __construct()
    {
    }

    function enableVerbose(bool $isVerbose): void
    {
        $this->verbose = $isVerbose;
    }

    function enableSmartSearch(bool $smartSearch): void
    {
        $this->smartSearch = $smartSearch;
    }

    function addExcludePaths(?string $exclude): void
    {
        $this->exclude = $exclude;
    }

    /**
     * Get expected exit code
     *
     * @return int
     */
    public function getExitCode(): int
    {
        return (int)!Unit::isSuccessful();
    }

    /**
     * Will Execute all unitary test files
     *
     * @param string $path
     * @param string|bool $rootDir
     * @param callable|null $callback
     * @return void
     * @throws BlunderSoftException
     */
    public function executeAll(string $path, string|bool $rootDir = false, ?callable $callback = null): void
    {
        $rootDir = is_string($rootDir) ? realpath($rootDir) : false;
        $path = (!$path && $rootDir !== false) ? $rootDir : $path;
        if($rootDir !== false && !str_starts_with($path, "/") && !str_starts_with($path, $rootDir)) {
            $path = $rootDir . "/" . $path;
        }
        $files = $this->findFiles($path, $rootDir);
        if (empty($files)) {
            /* @var string static::PATTERN */
            throw new BlunderSoftException("Unitary could not find any test files matching the pattern \"" .
                (TestDiscovery::PATTERN ?? "") . "\" in directory \"" . dirname($path) .
                "\" and its subdirectories.");
        } else {

            // Error Handler library
            $this->runBlunder();
            foreach ($files as $file) {
                $call = $this->requireUnitFile((string)$file, $callback);
                if ($call !== null) {
                    $call();
                }
            }
        }
    }

    /**
     * Prepares a callable that will include and execute a unit test file in isolation.
     *
     * Wrapping with Closure achieves:
     * Scope isolation, $this unbinding, State separation, Deferred execution
     *
     * @param string $file The full path to the test file to require.
     * @return Closure|null A callable that, when invoked, runs the test file.
     */
    private function requireUnitFile(string $file, ?callable $callback = null): ?Closure
    {
        $verbose = $this->verbose;

        $call = function () use ($file, $verbose, $callback): void {
            if (!is_file($file)) {
                throw new RuntimeException("File \"$file\" do not exists.");
            }

            self::$unitary = $callback($file);

            if(!(self::$unitary instanceof Unit)) {
                throw new \Exception("An instance of Unit must be return from callable in executeAll.");
            }

            $unitInst = require_once($file);
            if ($unitInst instanceof Unit) {
                self::$unitary = $unitInst;
            }
            $bool = self::$unitary->execute();

            if(!$bool && $verbose) {
                throw new BlunderSoftException(
                    "Could not find any tests inside the test file:\n" .
                    $file . "\n\n" .
                    "Possible causes:\n" .
                    "  • There are not test in test group/case.\n" .
                    "  • Unitary could not locate the Unit instance.\n" .
                    "  • You did not use the `group()` function.\n" .
                    "  • You created a new Unit in the test file but did not return it at the end. \n"
                );
            }
        };

        return $call->bindTo(null);
    }

    /**
     * Will Scan and find all unitary test files
     *
     * @param string $path
     * @param string|false $rootDir
     * @return array
     */
    private function findFiles(string $path, string|bool $rootDir = false): array
    {
        $files = [];
        $realDir = realpath($path);
        if ($realDir === false) {
            throw new RuntimeException("Directory \"$path\" does not exist. Try using a absolut path!");
        }

        if (is_file($path) && str_starts_with(basename($path), "unitary-")) {
            $files[] = $path;
        } else {
            if(is_file($path)) {
                $path = dirname($path) . "/";
            }
            if(is_dir($path)) {
                $files += $this->getFileIterateReclusive($path);
            }
        }
        // If smart search flag then step back if no test files have been found and try again
        if($rootDir !== false && count($files) <= 0 && str_starts_with($path, $rootDir) && $this->smartSearch) {
            $path = (string)realpath($path . "/..") . "/";
            return $this->findFiles($path, $rootDir);
        }
        return $files;
    }

    /**
     * Get exclude parameter
     *
     * @return array
     */
    private function exclude(): array
    {
        $excl = [];
        if ($this->exclude !== null) {
            $exclude = explode(',', $this->exclude);
            foreach ($exclude as $file) {
                $file = str_replace(['"', "'"], "", $file);
                $new = trim($file);
                $lastChar = substr($new, -1);
                if ($lastChar === DIRECTORY_SEPARATOR) {
                    $new .= "*";
                }
                $excl[] = trim($new);
            }
        }
        return $excl;
    }

    /**
     * Validate an exclude path
     *
     * @param array $exclArr
     * @param string $relativeDir
     * @param string $file
     * @return bool
     */
    private function findExcluded(array $exclArr, string $relativeDir, string $file): bool
    {
        $file = $this->getNaturalPath($file);
        foreach ($exclArr as $excl) {
            $relativeExclPath = $this->getNaturalPath($relativeDir . DIRECTORY_SEPARATOR . (string)$excl);
            if (fnmatch($relativeExclPath, $file)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Iterate files that match the expected patterns
     *
     * @param string $path
     * @return array
     */
    private function getFileIterateReclusive(string $path): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        /** @var string $pattern */
        $pattern = TestDiscovery::PATTERN;
        foreach ($iterator as $file) {
            if (($file instanceof SplFileInfo) && fnmatch($pattern, $file->getFilename()) &&
                ($this->exclude !== null || !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR))) {
                if (!$this->findExcluded($this->exclude(), $path, $file->getPathname())) {
                    $files[] = $file->getPathname();
                }
            }
        }
        return $files;
    }

    /**
     * Get a path as a natural path
     *
     * @param string $path
     * @return string
     */
    private function getNaturalPath(string $path): string
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Initialize Blunder error handler
     *
     * @return void
     */
    protected function runBlunder(): void
    {
        $run = new Run(new CliHandler());
        $run->setExitCode(1);
        $run->load();
    }

    /**
     * Get instance of Unit class
     *
     * This is primary used to access the main test Unit instance that is
     * pre-initialized for each test file. Is used by shortcut function like `group()`
     *
     * @return Unit
     */
    public static function getUnitaryInst(): Unit
    {
        if(self::$unitary === null) {
            throw new \BadMethodCallException('Unit has not been initiated.');
        }
        return self::$unitary;
    }
}
