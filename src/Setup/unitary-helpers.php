<?php
/**
 * unitary-helpers.php
 *
 * Provides global shortcut functions and safe prefixed versions for the Unitary testing framework.
 * The shortcut function is only defined if not already present, delegating to prefixed function `unitary_*()`.
 */

use MaplePHP\Unitary\TestConfig;
use MaplePHP\Unitary\Utils\FileIterator;

function unitary_group(string|TestConfig $message, Closure $expect, ?TestConfig $config = null): void
{
    FileIterator::getUnitaryInst()->group($message, $expect, $config);
}

if (!function_exists('group')) {
    function group(string|TestConfig $message, Closure $expect, ?TestConfig $config = null): void
    {
        unitary_group(...func_get_args());
    }
}
