<?php

namespace MaplePHP\Unitary\Kernel;

/**
 * Configure the kernels dispatched behavior
 */
class DispatchConfig extends \MaplePHP\Emitron\DispatchConfig
{

    protected string|bool $path = false;
    private ?int $exitCode = null;
    private bool $verbose = false;
    private bool $smartSearch = false;

    /**
     * Check if verbose is active
     *
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * Set if you want to show more possible warnings and errors that might be hidden
     *
     * @param bool $enableVerbose
     * @return $this
     */
    public function setVerbose(bool $enableVerbose): self
    {
        $this->verbose = $enableVerbose;
        return $this;
    }

    /**
     * Check if smart search is active
     *
     * @return bool
     */
    public function isSmartSearch(): bool
    {
        return $this->smartSearch;
    }

    /**
     * Enable smart search that will try to find test files automatically
     * even if missing from an expected path. Will add some overhead.
     *
     * @param bool $enableSmartSearch
     * @return $this
     */
    public function setSmartSearch(bool $enableSmartSearch): self
    {
        $this->smartSearch = $enableSmartSearch;
        return $this;
    }

    /**
     * Will return the expected test path
     *
     * @return string|bool
     */
    public function getPath(): string|bool
    {
        return $this->path;
    }

    /**
     * Get current exit code as int or null if not set
     *
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * Add exit after execution of the app has been completed
     *
     * @param int|null $exitCode
     * @return $this
     */
    public function setExitCode(int|null $exitCode): self
    {
        $this->exitCode = $exitCode;
        return $this;
    }
}