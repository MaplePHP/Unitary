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

enum UnitStatusType
{
    case Failure;
    case Error;

    public function getStatus(): string
    {
        return match($this) {
            self::Failure => 'failure',
            self::Error => 'error',
        };
    }
}
