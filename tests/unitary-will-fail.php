<?php
require_once(__DIR__ . "/TestLib/Mailer.php");
require_once(__DIR__ . "/TestLib/UserService.php");

use MaplePHP\Unitary\{Config\TestConfig, Expect, Mocker\MethodRegistry, TestCase, Unit};
use TestLib\Mailer;

$config = TestConfig::make("All A should fail")->withName("unitary-fail")->withSkip();
group($config, function (TestCase $case) {

    $case->describe("Default validations")->validate(1, function(Expect $inst) {
        $inst->isEmail();
        $inst->length(100, 1);
        $inst->isString();
    });

    $case->describe("Return validation")->validate(true, function(Expect $inst) {
        return false;
    });

    $case->describe("Assert validation")->validate(true, function(Expect $inst) {
        assert(1 == 2);
    });

    $case->describe("Assert with message validation")->validate(true, function(Expect $inst) {
        assert(1 == 2, "Is not equal to 2");
    });

    $case->describe("Assert with all validation")->validate(true, function(Expect $inst) {
        assert($inst->isEmail()->isString()->isValid(), "Is not email");
    });

    $case->add("HelloWorld", [
        "isInt" => [],
        "User validation" => function($value) {
            return $value === 2;
        }
    ], "Old validation syntax");

    // Mocks is deferred validations
    // Each mock count as a test (IS THIS RIGHT?)
    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("send")->keepOriginal()->called(0);
        $method->method("isValidEmail")->keepOriginal(); // Counts as PASS BUT WILL not be SHOWN
        $method->method("sendEmail")->keepOriginal()->called(0);
        $method->method("addBCC")
            ->isProtected()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "int")
            ->paramHasDefault(1, 1)
            ->paramIsOptional(0)
            ->paramIsReference(1)
            ->called(0);
    });

    $case->describe("Mocking validation")->validate(fn() => $mail->send(), function(Expect $inst) {
        $inst->hasThrowableMessage("dwdwqdwqwdq email");
    });

    $case->validate(false, function(Expect $inst) {
        $inst->isTrue();
    })->assert("A hard stop assert failure");

    $case->describe("Will mot validate because of assert above")->validate(false, function(Expect $inst) {
        $inst->isTrue();
    });
    //assert(1 == 2, "Assert in group level");
});