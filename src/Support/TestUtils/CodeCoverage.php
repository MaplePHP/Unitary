<?php

/**
 * Unit — Part of the MaplePHP Unitary CodeCoverage
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

declare(strict_types=1);

namespace MaplePHP\Unitary\Support\TestUtils;

use BadMethodCallException;
use MaplePHP\Unitary\Console\Enum\CoverageIssue;

class CodeCoverage
{
    private ?string $projectRootDir;
    private CoverageIssue $coverageIssue = CoverageIssue::None;
    private ?array $data = null;
    private ?string $path = null;
    private array $exclude = [];
    /** @var array<int, string> */
    private const DEFAULT_EXCLUDED_FILES = [
        "vendor",
        "tests",
        "test",
        "unitary-*",
        "unit-tests",
        "spec",
        "bin",
        "public",
        "storage",
        "bootstrap",
        "resources",
        "database",
        "config",
        "node_modules",
        "coverage-report",
        // Exclude below to protect against edge cases
        // (like someone accidentally putting a .php file in .github/scripts/ and including it)
        ".idea",
        ".vscode",
        ".git",
        ".github"
    ];

    public function __construct(?string $projectRootDir = null)
    {
        $this->projectRootDir = $projectRootDir;
        $this->exclude = self::DEFAULT_EXCLUDED_FILES;
    }

    /**
     * Check if Xdebug is enabled
     *
     * @return bool
     */
    public function hasXdebug(): bool
    {
        if ($this->hasIssue()) {
            return false;
        }
        if (!function_exists('xdebug_info')) {
            $this->coverageIssue = CoverageIssue::MissingXdebug;
            return false;
        }
        return true;
    }

    /**
     * Check if Xdebug has coverage mode enabled.
     *
     * @return bool
     */
    public function hasXdebugCoverage(): bool
    {
        if (!$this->hasXdebug()) {
            return false;
        }
        $mode = ini_get('xdebug.mode');
        if ($mode === false || !str_contains($mode, 'coverage')) {
            $this->coverageIssue = CoverageIssue::MissingCoverage;
            return false;
        }
        return true;
    }

    /**
     * Add files and directories to be excluded from coverage.
     *
     * By default, this method includes a set of common files and directories
     * that are typically excluded. To override and reset the list completely,
     * pass `true` as the second argument.
     *
     * @param array $exclude Additional files or directories to exclude.
     * @param bool $reset If true, replaces the default excluded list instead of merging with it.
     * @return void
     */
    public function exclude(array $exclude, bool $reset = false): void
    {
        $this->exclude = (!$reset) ? array_merge(self::DEFAULT_EXCLUDED_FILES, $exclude) : $exclude;
    }

    /**
     * Start coverage listening
     *
     * @psalm-suppress UndefinedFunction
     * @psalm-suppress UndefinedConstant
     * @param string|null $path (start path to include from)
     * @return void
     */
    public function start(?string $path = null): void
    {
        $this->path = $path;
        $this->data = [];
        if ($this->hasXdebugCoverage()) {
            \xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
    }

    /**
     * End coverage listening
     *
     * @psalm-suppress UndefinedFunction
     * @noinspection PhpUndefinedFunctionInspection
     *
     * @return void
     */
    public function end(): void
    {
        if ($this->data === null) {
            throw new BadMethodCallException("You must start code coverage before you can end it");
        }
        if ($this->hasXdebugCoverage()) {

            $this->data = \xdebug_get_code_coverage();
            \xdebug_stop_code_coverage();

        }
    }

    /**
     * This is a simple exclude checker used to exclude a file, directories or files in a pattern
     * with the help of wildcard, for example, "unitary-*" will exclude all files with prefix unitary.
     *
     * @param string $file
     * @return bool
     */
    protected function excludePattern(string $file): bool
    {
        $filename = basename($file);
        $file = str_replace($this->projectRootDir, "", $file);
        foreach ($this->exclude as $pattern) {
            if (preg_match('#/' . preg_quote($pattern, '#') . '(/|$)#', $file)) {
                return true;
            }
            if (str_ends_with($pattern, '*')) {
                $prefix = substr($pattern, 0, -1);
                if (str_starts_with($filename, $prefix)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get a Coverage result, will return false if there is an error
     *
     * @return array|false
     */
    public function getResponse(): array|false
    {
        if ($this->hasIssue()) {
            return false;
        }
        $totalLines = 0;
        $executedLines = 0;
        foreach ($this->data as $file => $lines) {
            if ($this->excludePattern($file) || !($this->path !== null && str_starts_with($file, $this->path))) {
                continue;
            }
            //Add key "=> $line" for line numbers
            foreach ($lines as $status) {
                if ($status === -2) {
                    continue;
                }
                $totalLines++;
                if ($status === 1) {
                    $executedLines++;
                }
            }
        }
        $percent = $totalLines > 0 ? round(($executedLines / $totalLines) * 100, 2) : 0;
        return [
            'totalLines' => $totalLines,
            'executedLines' => $executedLines,
            'percent' => $percent
        ];
    }

    /**
     * Get raw data
     *
     * @return array
     */
    public function getRawData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @return CoverageIssue
     */
    public function getIssue(): CoverageIssue
    {
        return $this->coverageIssue;
    }

    /**
     * Check if error exists
     *
     * @return bool
     */
    public function hasIssue(): bool
    {
        return $this->coverageIssue !== CoverageIssue::None;
    }
}
