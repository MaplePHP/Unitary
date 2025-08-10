<?php

use MaplePHP\Unitary\{TestCase, TestConfig, Expect};

$config = TestConfig::make()->withName("unitary-test");

group("Hello world 0", function(TestCase $case) {

    $case->assert(1 === 2, "wdwdq 2");

}, $config);

group("Hello world 1", function(TestCase $case) {

    $case->validate(1 === 2, function(Expect $expect) {
        $expect->isEqualTo(true);
    });

}, $config);

group($config->withSubject("Hello world 2"), function(TestCase $case) {
    $case->validate(2 === 2, fn() => true);
});