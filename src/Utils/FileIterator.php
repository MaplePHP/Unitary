<?php

declare(strict_types=1);

namespace MaplePHP\Unitary\Utils;

use Closure;
use Exception;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class FileIterator
{
    public const PATTERN = 'unitary-*.php';

    private array $args;

    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * Will Execute all unitary test files.
     * @param string $path
     * @param string|bool $rootDir
     * @return void
     * @throws BlunderSoftException
     */
    public function executeAll(string $path, string|bool $rootDir = false): void
    {

        $rootDir = $rootDir !== false ? realpath($rootDir) : false;
        $path = (!$path && $rootDir) ? $rootDir : $path;

        if($rootDir !== false && !str_starts_with($path, $rootDir)) {
            throw new RuntimeException("The test search path (\$path) \"" . $path . "\" does not have the root director (\$rootDir) of \"" . $rootDir . "\".");
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
                Unit::resetUnit();
                Unit::setHeaders([
                    "args" => $this->args,
                    "file" => $file,
                    "checksum" => md5((string)$file)
                ]);

                $call = $this->requireUnitFile((string)$file);
                if ($call !== null) {
                    $call();
                }
                if (!Unit::hasUnit()) {
                    throw new RuntimeException("The Unitary Unit class has not been initiated inside \"$file\".");
                }
            }
            Unit::completed();
            exit((int)Unit::isSuccessful());
        }
    }

    /**
     * Will Scan and find all unitary test files
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
            }
        }

        if($rootDir && count($files) <= 0 && str_starts_with($path, $rootDir)) {
            $path = realpath($path . "/..") . "/";
            return $this->findFiles($path, $rootDir);
        }

        return $files;
    }

    /**
     * Get exclude parameter
     * @return array
     */
    public function exclude(): array
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
     * @param array $exclArr
     * @param string $relativeDir
     * @param string $file
     * @return bool
     */
    public function findExcluded(array $exclArr, string $relativeDir, string $file): bool
    {
        $file = $this->getNaturalPath($file);
        foreach ($exclArr as $excl) {
            /* @var string $excl */
            $relativeExclPath = $this->getNaturalPath($relativeDir . DIRECTORY_SEPARATOR . $excl);
            if (fnmatch($relativeExclPath, $file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a path as a natural path
     * @param string $path
     * @return string
     */
    public function getNaturalPath(string $path): string
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Require a file without inheriting any class information
     * @param string $file
     * @return Closure|null
     */
    private function requireUnitFile(string $file): ?Closure
    {
        $clone = clone $this;
        $call = function () use ($file, $clone): void {
            $cli = new CliHandler();

            if (Unit::getArgs('trace') !== false) {
                $cli->enableTraceLines(true);
            }
            $run = new Run($cli);
            $run->setExitCode(1);
            $run->load();

            //ob_start();
            if (!is_file($file)) {
                throw new RuntimeException("File \"$file\" do not exists.");
            }
            require_once($file);

            $clone->getUnit()->execute();

            /*
            $outputBuffer = ob_get_clean();
            if (strlen($outputBuffer) && Unit::hasUnit()) {
                $clone->getUnit()->buildNotice("Note:", $outputBuffer, 80);
            }
             */
        };
        return $call->bindTo(null);
    }

    /**
     * @return Unit
     * @throws RuntimeException|Exception
     */
    protected function getUnit(): Unit
    {
        $unit = Unit::getUnit();
        if ($unit === null) {
            throw new RuntimeException("The Unit instance has not been initiated.");
        }
        return $unit;

    }
}
