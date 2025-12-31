<?php

use MaplePHP\Unitary\{Config\TestConfig, Expect, TestCase};

$config = TestConfig::make()->withName("unitary");


group("Validating API Response", function(TestCase $case) {

    $json = '{"response":{"status":200,"message":"ok"}}';

    $case->expect($json)
        ->isJson()
        ->describe("Response must be valid JSON")
        ->hasJsonValueAt("response.status", 200)
        ->describe("Response status must be 200")
        ->validate("API response status");

});

group($config->withSubject("Assert validations"), function (TestCase $case) {



    $case->defer(function() use ($case) {
        // Deferred to execute last
        $case->expect("HelloWorld0")
            ->isEqualTo("Hello World5")
            ->isEqualTo("Hello World6")
            ->validate("HELOWOOWOW0");
    });


    $case->expect("HelloWorld")
        ->isEqualTo("Hello World")
        ->isEqualTo("Hello World2")
        ->assert("HELOWOOWOW1");

    $case->expect("HelloWorld2")
        ->isEqualTo("Hello World3")
        ->isEqualTo("Hello World4")
        ->validate("HELOWOOWOW2");


});

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