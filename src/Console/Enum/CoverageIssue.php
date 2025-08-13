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

enum CoverageIssue
{
    case None;
    case MissingXdebug;
    case MissingCoverage;

    public function message(): string
    {
        return match($this) {
            self::None => 'No error occurred.',
            self::MissingXdebug => 'Xdebug is not installed or enabled.',
            self::MissingCoverage => 'Xdebug is enabled, but coverage mode is missing.',
        };
    }
}