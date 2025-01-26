<?php

declare(strict_types=1);

namespace MaplePHP\Unitary;

class TestMem
{
    private float $startTime;
    private int $startMemory;
    //private static int $correction = 0;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    /**
     * Get execution time
     * @return float
     */
    public function getExecutionTime(): float
    {
        $endTime = microtime(true);
        return $endTime - $this->startTime;
    }

    /**
     * Get memory usage in KB
     * @return float
     */
    public function getMemoryUsage(): float
    {
        $endMemory = memory_get_usage();
        return ($endMemory - $this->startMemory) / 1024;
    }

    /**
     * Get peak memory usage in KB
     * @return float
     */
    public function getMemoryPeakUsage(): float
    {
        return (memory_get_peak_usage() / 1024);
    }
}
