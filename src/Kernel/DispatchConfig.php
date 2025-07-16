<?php

namespace MaplePHP\Unitary\Kernel;

/**
 * Configure the kernels dispatched behavior
 */
class DispatchConfig
{

    private ?int $exitCode = null;

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