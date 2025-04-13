
<?php

use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\Unit;
use MaplePHP\Validate\ValidatePool;
use MaplePHP\Unitary\Mocker\MethodPool;


class Mailer
{
    public $from = "";
    public $bcc = "";
    public function sendEmail(string $email, string $name = "daniel"): string
    {
        if(!$this->isValidEmail($email)) {
            throw new \Exception("Invalid email");
        }
        return "Sent email";
    }

    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function getFromEmail(string $email): string
    {
        return $this->from;
    }

    /**
     * Add from email address
     *
     * @param string $email
     * @return void
     */
    public function addFromEmail(string $email): void
    {
        $this->from = $email;
    }

    public function addBCC(string $email): void
    {
        $this->bcc = $email;
    }

}

class UserService {
    public function __construct(private Mailer $mailer) {}

    public function registerUser(string $email, string $name = "Daniel"): void {
        // register user logic...

        if(!$this->mailer->isValidEmail($email)) {
            throw new \Exception("Invalid email");
        }
        echo $this->mailer->sendEmail($email, $name)."\n";
        echo $this->mailer->sendEmail($email, $name);
    }
}


$unit = new Unit();
$unit->group("Unitary test 2", function (TestCase $inst) {

    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addFromEmail")
            ->isPublic()
            ->hasDocComment()
            ->hasReturnType()
            ->count(0);

        $pool->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->count(0);
    });
    $service = new UserService($mock);


    $inst->validate("yourTestValue", function(ValidatePool $inst) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();
        $inst->isString();
        $inst->isResource();
    });

    // Example 1
    /*

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

    /*
     $inst->validate("yourTestValue", function(ValidatePool $inst, mixed $value) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();
        $inst->isString();
        $inst->isResource();
        return ($value === "yourTestValue1");
    });

    $inst->validate("yourTestValue", fn(ValidatePool $inst) => $inst->isfloat());
     */

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

