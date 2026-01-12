<?php

use MaplePHP\Unitary\{Config\TestConfig, Expect, TestCase, TestItem};


group(TestConfig::make("Test item class")->withName("unitary-test-item"), function (TestCase $case) {

    $item = new TestItem();

    $item = $item
        ->setValidation("validation")
        ->setValidationArgs(["arg1", "arg2"])
        ->setIsValid(true)
        ->setValue("value")
        ->setCompareToValue("compare")
        ->setHasArgs(true);

    $case->validate($item->isValid(), function(Expect $valid) {
        $valid->isTrue();

    })->describe("Testing TestItem is validMethod");

    $case->validate($item->getValidation(), function(Expect $valid) {
        $valid->isEqualTo("validation");
    });

    $case->validate($item->getValidationArgs(), function(Expect $valid) {
        $valid->isInArray("arg1");
        $valid->isInArray("arg2");
        $valid->isCountEqualTo(2);
    });

    $case->validate($item->getValue(), function(Expect $valid) {
        $valid->isEqualTo("value");
    });

    $case->validate($item->hasComparison(), function(Expect $valid) {
        $valid->isTrue();
    });

    $case->validate($item->getCompareValues(), function(Expect $valid) {
        $valid->isInArray("compare");
    });

    $case->validate($item->getComparison(), function(Expect $valid) {
        $valid->isEqualTo('Expected: "value" | Actual: "compare"');
    });

    $case->validate($item->getStringifyArgs(), function(Expect $valid) {
        $valid->isEqualTo('(arg1, arg2)');
    });

    $case->validate($item->getValidationTitle(), function(Expect $valid) {
        $valid->isEqualTo('validation(arg1, arg2)');
    });

    $case->validate($item->getValidationLength(), function(Expect $valid) {
        $valid->isEqualTo(10);
    });

    $case->validate($item->getValidationLengthWithArgs(), function(Expect $valid) {
        $valid->isEqualTo(22);
    });

    $case->validate($item->getStringifyValue(), function(Expect $valid) {
        $valid->isEqualTo('"value"');
    });

    $case->validate($item->getCompareToValue(), function(Expect $valid) {
        $valid->isInArray( '"compare"');
    });

});
