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

namespace MaplePHP\Unitary\Console\Enum;

enum Severity: string
{
    case Low = "low";
    case Medium = "medium";
    case High = "high";

    /**
     * Get severity index from low to high
     *
     * @return int
     */
    public function index(): int
    {
        return match($this) {
            self::Low => 0,
            self::Medium => 1,
            self::High => 2,
        };
    }

    /**
     * Get severity as title/label
     *
     * @return string
     */
    public function title(): string
    {
        return ucfirst($this->value);
    }
}

