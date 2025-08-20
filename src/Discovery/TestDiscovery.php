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
use ErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use UnexpectedValueException;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\Unit;

final class TestDiscovery
{
    private string $pattern = '*/unitary-*.php';
    private bool $verbose = false;
    private bool $smartSearch = false;
    private ?array $exclude = null;
    private static ?Unit $unitary = null;

    /**
     * Enable verbose flag which will show errors that should not always be visible
     *
     * @param bool $isVerbose
     * @return $this
     */
    public function enableVerbose(bool $isVerbose): self
    {
        $this->verbose = $isVerbose;
        return $this;
    }

    /**
     * Enabling smart search; If no tests I found Unitary will try to traverse
     * backwards until a test is found
     *
     * @param bool $smartSearch
     * @return $this
     */
    public function enableSmartSearch(bool $smartSearch): self
    {
        $this->smartSearch = $smartSearch;
        return $this;
    }

    /**
     * Exclude paths from file iteration
     *
     * @param string|array|null $exclude
     * @return $this
     */
    public function addExcludePaths(string|array|null $exclude): self
    {
        if ($exclude !== null) {
            $this->exclude = is_string($exclude) ? explode(', ', $exclude) : $exclude;
        }
        return $this;
    }

    /**
     * Change the default test discovery pattern from `unitary-*.php` to a custom pattern.
     *
     * Notes:
     * - Wildcards can be used for paths (`tests/`) and files (`unitary-*.php`).
     * - If no file extension is specified, `.php` is assumed.
     * - Only PHP files are supported as test files.
     *
     * @param ?string $pattern null value will fall back to the default value
     * @return $this
     */
    public function setDiscoverPattern(?string $pattern): self
    {
        if ($pattern !== null) {
            $pattern = rtrim($pattern, '*');
            $pattern = ltrim($pattern, '*');
            $pattern = ltrim($pattern, '/');
            $this->pattern = "*/" . (!str_ends_with($pattern, '.php') ? rtrim($pattern, '/') . "/*.php" : $pattern);
        }
        return $this;
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
     * @throws BlunderErrorException
     * @throws BlunderSoftException
     * @throws ErrorException
     * @throws Throwable
     */
    public function executeAll(string $path, string|bool $rootDir = false, ?callable $callback = null): void
    {
        $rootDir = is_string($rootDir) ? realpath($rootDir) : false;
        $path = (!$path && $rootDir !== false) ? $rootDir : $path;
        if ($rootDir !== false && !str_starts_with($path, "/") && !str_starts_with($path, $rootDir)) {
            $path = $rootDir . "/" . $path;
        }
        $files = $this->findFiles($path, $rootDir);

        // Init Blunder error handling framework
        $this->runBlunder();

        if (empty($files) && $this->verbose) {
            throw new BlunderSoftException("Unitary could not find any test files matching the pattern \"" .
                $this->pattern . "\" in directory \"" . dirname($path) .
                "\" and its subdirectories.");
        } else {
            foreach ($files as $file) {
                $this->executeUnitFile((string)$file, $callback);
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
     * @param Closure $callback
     * @return void
     * @throws ErrorException
     * @throws BlunderErrorException
     * @throws Throwable
     */
    private function executeUnitFile(string $file, Closure $callback): void
    {
        $verbose = $this->verbose;
        if (!is_file($file)) {
            throw new RuntimeException("File \"$file\" do not exists.");
        }

        $instance = $callback($file);
        if (!$instance instanceof Unit) {
            throw new UnexpectedValueException('Callable must return ' . Unit::class);
        }
        self::$unitary = $instance;

        $unitInst = $this->isolateRequire($file);

        if ($unitInst instanceof Unit) {
            $unitInst->inheritConfigs(self::$unitary);
            self::$unitary = $unitInst;
        }
        $ok = self::$unitary->execute();

        if (!$ok && $verbose) {
            trigger_error(
                "Could not find any tests inside the test file:\n$file\n\nPossible causes:\n" .
                "  • There are no test in test group/case.\n" .
                "  • Unitary could not locate the Unit instance.\n" .
                "  • You did not use the `group()` function.\n" .
                "  • You created a new Unit in the test file but did not return it at the end.",
                E_USER_WARNING
            );
        }

    }

    /**
     * Isolate the required file and keep $this out of scope
     *
     * @param $file
     * @return mixed
     */
    private function isolateRequire($file): mixed
    {
        return (static function (string $f) {
            return require $f;
        })($file);
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
            if (is_file($path)) {
                $path = dirname($path) . "/";
            }
            if (is_dir($path)) {
                $files += $this->getFileIterateReclusive($path);
            }
        }
        // If smart search flag then step back if no test files have been found and try again
        if ($rootDir !== false && count($files) <= 0 && str_starts_with($path, $rootDir) && $this->smartSearch) {
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
        if ($this->exclude !== null && $this->exclude !== []) {
            foreach ($this->exclude as $file) {
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
        foreach ($iterator as $file) {
            if (($file instanceof SplFileInfo) && fnmatch($this->pattern, $file->getPathname()) &&
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
        $run->severity()
            ->excludeSeverityLevels([E_USER_WARNING])
            ->redirectTo(function () {
                // Let PHP’s default error handler process excluded severities
                return false;
            });
        $run->setExitCode(1);
        $run->load();
    }

    /**
     * Get instance of Unit class
     *
     * This is primary used to access the main test Unit instance that is
     * pre-initialized for each test file. Is used by shortcut function like `group()`
     *
     * @return Unit|null
     */
    public static function getUnitaryInst(): ?Unit
    {
        return self::$unitary;
    }
}
