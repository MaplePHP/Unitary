<?php

/**
 * Default configs, that exists in MaplePHP Unitary
 */
return [
    //'path' => 'app/Libraries/Unitary/tests/unitary-test.php',
    'type' => "cli",
    'path' => false, // false|string|array<int, string>
    'smart-search' => false, // bool
    'errors-only' => false, // bool
    'verbose' => false, // bool
    'exclude' => false, // false|string|array<int, string>
    'discover-pattern' => false, // string|false (paths (`tests/`) and files (`unitary-*.php`).)
    'show' => false,
    'timezone' => 'Europe/Stockholm',
    'locale' => 'en_US',
    'always-show-files' => false,
    'fail-fast' => false, // bool
    //'exit_error_code' => 1, ??
];
