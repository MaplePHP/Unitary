<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

use Closure;
use RuntimeException;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileIterator
{
    const PATTERN = 'unitary-*.php';

    private array $args;

    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * Will Execute all unitary test files.
     * @param string $directory
     * @return void
     * @throws RuntimeException
     */
    public function executeAll(string $directory): void
    {
        $files = $this->findFiles($directory);
        if (empty($files)) {
            throw new RuntimeException("No files found matching the pattern \"" . static::PATTERN . "\" in directory \"$directory\" ");
        } else {
            foreach ($files as $file) {
                extract($this->args, EXTR_PREFIX_SAME, "wddx");
                Unit::resetUnit();
                Unit::setHeaders([
                    "args" => $this->args,
                    "file" => $file,
                    "checksum" => md5($file)
                ]);

                $this->requireUnitFile($file)();
                if(!Unit::hasUnit()) {
                    throw new RuntimeException("The Unitary Unit class has not been initiated inside \"$file\".");
                }
            }

            Unit::completed();
        }
    }

    /**
     * Will Scan and find all unitary test files
     * @param $dir
     * @return array
     */
    private function findFiles($dir): array
    {
        $files = [];
        $realDir = realpath($dir);
        if(!$realDir) {
            throw new RuntimeException("Directory \"$dir\" does not exist. Try using a absolut path!");
        }
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
     * Require file without inheriting any class information
     * @param string $file
     * @return Closure
     */
    private function requireUnitFile(string $file): Closure
    {
        $call = function() use ($file): void
        {

            $cli = new CliHandler();
            if(isset(self::$headers['args']['trace'])) {
                $cli->enableTraceLines(true);
            }
            $run = new Run($cli);
            $run->load();

            ob_start();
            require_once ($file);
            Unit::getUnit()->execute();

            $outputBuffer = ob_get_clean();
            if($outputBuffer && Unit::hasUnit()) {
                Unit::getUnit()->buildNotice("Note:", $outputBuffer, 80);
            }
        };
        return $call->bindTo(null);
    }
}