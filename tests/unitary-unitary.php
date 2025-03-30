<?php

use MaplePHP\Unitary\TestWrapper;
use MaplePHP\Unitary\Unit;


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

$unit->add("Unitary test", function () {


    $mock = $this->mock(Mailer::class, function ($mock) {
        //$mock->method("sendEmail")->return("SENT2121");
    });
    $service = new UserService($mock);

    $service->registerUser('user@example.com');


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
