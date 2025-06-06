<?php
require_once(__DIR__ . "/TestLib/Mailer.php");
require_once(__DIR__ . "/TestLib/UserService.php");

use MaplePHP\Unitary\{Mocker\MethodRegistry, TestCase, TestConfig, Expect, Unit};
use TestLib\Mailer;

$unit = new Unit();
$config = TestConfig::make("All A should fail")->withName("unitary-fail")->withSkip();
$unit->group($config, function (TestCase $case) use($unit) {

    $case->error("Default validations")->validate(1, function(Expect $inst) {
        $inst->isEmail();
        $inst->length(100, 1);
        $inst->isString();
    });

    $case->error("Return validation")->validate(true, function(Expect $inst) {
        return false;
    });

    $case->error("Assert validation")->validate(true, function(Expect $inst) {
        assert(1 == 2);
    });

    $case->error("Assert with message validation")->validate(true, function(Expect $inst) {
        assert(1 == 2, "Is not equal to 2");
    });

    $case->error("Assert with all validation")->validate(true, function(Expect $inst) {
        assert($inst->isEmail()->isString()->isValid(), "Is not email");
    });

    $case->add("HelloWorld", [
        "isInt" => [],
        "User validation" => function($value) {
            return $value === 2;
        }
    ], "Old validation syntax");

    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("send")->keepOriginal()->called(0);
        $method->method("isValidEmail")->keepOriginal();
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

    $case->error("Mocking validation")->validate(fn() => $mail->send(), function(Expect $inst) {
        $inst->hasThrowableMessage("dwdwqdwqwdq email");
    });
    assert(1 == 2, "Assert in group level");
});