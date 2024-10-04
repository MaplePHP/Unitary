<?php
use MaplePHP\Unitary\Unit;

$unit = new Unit();

$unit->add("Unitary test", function () {

    $this->add("Lorem ipsum dolor", [
        "isString" => [],
        "length" => [1,200]

    ])->add(92928, [
        "isInt" => []

    ])->add("Lorem", [
        "isString" => [],
        "length" => function () {
            return $this->length(1, 50);
        }
    ], "The length is not correct!");

});
