<?php
/**
 * unitary-helpers.php
 *
 * Provides global shortcut functions and safe prefixed versions for the Unitary testing framework.
 * The shortcut function is only defined if not already present, delegating to prefixed function `unitary_*()`.
 */

use MaplePHP\Unitary\Config\TestConfig;
use MaplePHP\Unitary\Discovery\TestDiscovery;

function unitary_group(string|TestConfig $message, Closure $expect, ?TestConfig $config = null): void
{
    TestDiscovery::getUnitaryInst()->group($message, $expect, $config);
}

if (!function_exists('group')) {
    function group(string|TestConfig $message, Closure $expect, ?TestConfig $config = null): void
    {
        unitary_group(...func_get_args());
    }
}
