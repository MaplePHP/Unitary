<?php

/**
 * assert-polyfill.php
 *
 * Ensures consistent assert() behavior across PHP versions.
 */

if (PHP_VERSION_ID < 80400) {
    if (ini_get('assert.active') === false) {
        ini_set('assert.active', 1);
    }

    if (ini_get('assert.exception') === false) {
        ini_set('assert.exception', 1);
    }
}
