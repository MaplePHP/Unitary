#!/usr/bin/env php
<?php
/**
 * This is how a template test file should look like but
 * when used in MaplePHP framework you can skip the "bash code" at top and the "autoload file"!
 */
use MaplePHP\Unitary\Unit;

if (!class_exists(Unit::class)) {
    $dir = realpath(dirname(__FILE__)."/..");
    if(is_file("$dir/vendor/autoload.php")) {
        require_once("$dir/vendor/autoload.php");
    } else {
        die("Please run 'composer install' before running the example test suite");
    }
}

// If you add true to Unit it will run in quite mode
// and only report if it finds any errors!
$unit = new Unit(true);

// Add a title to your tests (not required)
$unit->addTitle("Testing MaplePHP Unitary library!");
$unit->add("Checking data type", function($inst) {

    $inst->add("Lorem ipsum dolor", [
        "string" => [],
        "length" => [1,200]

    ])->add(92928, [
        "int" => []

    ])->add("Lorem", [
        "string" => [],
        "length" => function($valid) {
            return $valid->length(1, 50);
        }
    ], "The length is not correct!");

});

$unit->execute();

