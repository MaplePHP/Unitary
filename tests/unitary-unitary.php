<?php

use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\TestWrapper;
use MaplePHP\Unitary\Unit;
use MaplePHP\Validate\Inp;
use MaplePHP\Validate\ValidatePool;


class Mailer
{
    function sendEmail(string $email): string
    {
        echo "Sent email to $email";
        return "SENT!!";
    }
}

class UserService {
    public function __construct(private Mailer $mailer) {}

    public function registerUser(string $email): void {
        // register user logic...
        echo $this->mailer->sendEmail($email)."\n";
        echo $this->mailer->sendEmail($email);
    }
}


$unit = new Unit();

$unit->group("Unitary test", function (TestCase $inst) {


    // Example 1
    /*
     $mock = $this->mock(Mailer::class, function ($mock) {
        $mock->method("testMethod1")->count(1)->return("lorem1");
        $mock->method("testMethod2")->count(1)->return("lorem1");
    });
    $service = new UserService($mock);

    // Example 2
    $mock = $this->mock(Mailer::class, [
        "testMethod1" => [
            "count" => 1,
            "validate" => [
                "equal" => "lorem1",
                "contains" => "lorem",
                "length" => [1,6]
            ]
        ]
    ]);
    $service = new UserService($mock);
    $service->registerUser('user@example.com');
     */

    $inst->validate("yourTestValue", function(ValidatePool $inst, mixed $value) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();

        return ($value === "yourTestValue1");
    });

    //$inst->listAllProxyMethods(Inp::class);
//->error("Failed to validate yourTestValue (optional error message)")



    /*
     * $mock = $this->mock(Mailer::class);
echo "ww";

    $service = new UserService($test);
    $service->registerUser('user@example.com');
    var_dump($mock instanceof Mailer);
    $service = new UserService($mock);
    $service->registerUser('user@example.com');
     */

    $this->add("Lorem ipsum dolor", [
        "isString" => [],
        "length" => [1,300]

    ])->add(92928, [
        "isInt" => []

    ])->add("Lorem", [
        "isString" => [],
        "length" => function () {
            return $this->length(1, 50);
        }
    ], "The length is not correct!");

});
