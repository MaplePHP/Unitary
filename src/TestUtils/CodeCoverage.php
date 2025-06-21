<?php

namespace MaplePHP\Unitary\TestUtils;

use BadMethodCallException;

class CodeCoverage
{

    /** @var array<string> */
    const ERROR = [
        "No error",
        "Xdebug is not available",
        "Xdebug is enabled, but coverage mode is missing"
    ];

    private ?array $data = null;
    private int $errorCode = 0;

    private array $allowedDirs = [];
    private array $excludeDirs = [
        "vendor",
        "tests",
        "test",
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


    /**
     * Check if Xdebug is enabled
     *
     * @return bool
     */
    public function hasXdebug(): bool
    {
        if($this->errorCode > 0) {
            return false;
        }
        if (!function_exists('xdebug_info')) {
            $this->errorCode = 1;
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
        if(!$this->hasXdebug()) {
            return false;
        }
        $mode = ini_get('xdebug.mode');
        if ($mode === false || !str_contains($mode, 'coverage')) {
            $this->errorCode = 1;
            return false;
        }
        return true;
    }


    public function exclude(string|array $path): void
    {

    }

    public function whitelist(string|array $path): void
    {

    }

    /**
     * Start coverage listening
     *
     * @psalm-suppress UndefinedFunction
     * @psalm-suppress UndefinedConstant
     * @noinspection PhpUndefinedFunctionInspection
     * @noinspection PhpUndefinedConstantInspection
     *
     * @return void
     */
    public function start(): void
    {
        $this->data = [];
        if($this->hasXdebugCoverage()) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
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
        if($this->data === []) {
            throw new BadMethodCallException("You must start code coverage before you can end it");
        }
        if($this->hasXdebugCoverage()) {

            $this->data = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
        }
    }

    /**
     * Get a Coverage result, will return false if there is an error
     *
     * @return array|false
     */
    public function getResponse(): array|false
    {
        if($this->errorCode > 0) {
            return false;
        }
        return $this->data;
    }

    /**
     * Get an error message
     *
     * @return string
     */
    public function getError(): string
    {
        return self::ERROR[$this->errorCode];
    }

    /**
     * Get an error code
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Check if error exists
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return ($this->errorCode > 0);
    }
}