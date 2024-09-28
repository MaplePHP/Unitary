#!/usr/bin/env php
<?php
/**
 * This is how a template test file should look like but
 * when used in MaplePHP framework you can skip the "bash code" at top and the "autoload file"!
 */
use MaplePHP\Unitary\Unit;

// If you add true to Unit it will run in quite mode
// and only report if it finds any errors!

//throw new \Exception("Test error handler");
$unit = new Unit();

$unit->manual("unitary")->add("Unitary test", function() {

    $this->add("Lorem ipsum dolor", [
        "isInt" => [],
        "length" => [1,200]

    ])->add(92928, [
        "isInt" => []

    ])->add("Lorem", [
        "isString" => [],
        "length" => function() {
            return $this->length(1, 50);
        }
    ], "The length is not correct!");


});

$unit->execute();

