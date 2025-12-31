<?php

use MaplePHP\Unitary\{Config\TestConfig, Expect, TestCase};

$config = TestConfig::make()->withName("unitary");

group($config->withSubject("Assert validations"), function (TestCase $case) {

    $case->validate("HelloWorld", function(Expect $inst) {
        assert($inst->isEqualTo("HelloWorld")->isValid(), "Assert has failed");
    });
    assert(1 === 1, "Assert has failed");

});

group("Example API Response", function(TestCase $case) {

    $case->validate('{"response":{"status":200,"message":"ok"}}', function(Expect $expect) {
        $expect->isJson()
               ->hasJsonValueAt("response.status", 200)
               ->assert("Json status response is invalid");;
    })->describe("Checking PSR Response");

    $case->validate('{"response":{"status":200,"message":"ok"}}', function(Expect $expect) {
        $expect->isJson()
            ->hasJsonValueAt("response.status", 200)
            ->validate("Json status response is invalid");;
    })->describe("Checking PSR Response");

});

group($config->withSubject("Tets old validation syntax"), function ($case) {
    $case->add("HelloWorld", [
        "isString" => [],
        "User validation" => function($value) {
            return $value === "HelloWorld";
        }
    ], "Is not a valid port number");

    $case->add("HelloWorld", [
        "isEqualTo" => ["HelloWorld"],
    ], "Failed to validate");
});

group($config->withSubject("Test json validation"), function(TestCase $case) {

    $case->validate('{"response":{"status":200,"message":"ok"}}', function(Expect $expect) {

        $expect->isJson()->hasJsonValueAt("response.status", 200);
        assert($expect->isValid(), "Expected JSON structure did not match.");

    })->describe("Test json validation");

});