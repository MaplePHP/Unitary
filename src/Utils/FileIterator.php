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

namespace MaplePHP\Unitary\Utils;

use Closure;
use Exception;
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

final class FileIterator
{
    public const PATTERN = 'unitary-*.php';

    private array $args;
    private bool $verbose = false;
    private bool $smartSearch = false;
    private bool $exitScript = true;
    private ?Command $command = null;
    private BodyInterface $handler;
    private static ?Unit $unitary = null;

    public function __construct(BodyInterface $handler, array $args = [])
    {
        $this->args = $args;
        $this->handler = $handler;
    }


    function enableSmartSearch(bool $isVerbose): void
    {
        $this->verbose = $isVerbose;
    }

    function enableVerbose(bool $isVerbose): void
    {
        $this->verbose = $isVerbose;
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
                (FileIterator::PATTERN ?? "") . "\" in directory \"" . dirname($path) .
                "\" and its subdirectories.");
        } else {
            foreach ($files as $file) {
                extract($this->args, EXTR_PREFIX_SAME, "wddx");

                // DELETE
                Unit::resetUnit();

                // DELETE (BUT PASSS)
                Unit::setHeaders([
                    "args" => $this->args,
                    "file" => $file,
                    "checksum" => md5((string)$file)
                ]);

                // Error Handler library
                $this->runBlunder();

                $call = $this->requireUnitFile((string)$file);

                if ($call !== null) {
                    $call();
                }

                if($callback !== null) {
                    $callback();
                }
            }
            Unit::completed();
            if ($this->exitScript) {
                $this->exitScript();
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
    private function requireUnitFile(string $file): ?Closure
    {

        $handler = $this->handler;
        $verbose = $this->verbose;

        $call = function () use ($file, $handler, $verbose): void {
            if (!is_file($file)) {
                throw new RuntimeException("File \"$file\" do not exists.");
            }
            self::$unitary = new Unit($handler);
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
     * You can change the default exist script from enabled to disable
     *
     * @param $exitScript
     * @return void
     */
    public function enableExitScript($exitScript): void
    {
        $this->exitScript = $exitScript;
    }

    /**
     * Exist the script with the right expected number
     *
     * @return void
     */
    public function exitScript(): void
    {
        exit($this->getExitCode());
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
        if($rootDir !== false && count($files) <= 0 && str_starts_with($path, $rootDir) && isset($this->args['smart-search'])) {
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
        if (isset($this->args['exclude']) && is_string($this->args['exclude'])) {
            $exclude = explode(',', $this->args['exclude']);
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
        $pattern = FileIterator::PATTERN;
        foreach ($iterator as $file) {
            if (($file instanceof SplFileInfo) && fnmatch($pattern, $file->getFilename()) &&
                (!empty($this->args['exclude']) || !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR))) {
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
