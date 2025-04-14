
<?php

use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\Unit;
use MaplePHP\Validate\ValidatePool;
use MaplePHP\Unitary\Mocker\MethodPool;


class Mailer
{
    public $from = "";
    public $bcc = "";


    public function __construct(string $arg1)
    {

    }

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
    public function addFromEmail($email): void
    {
        $this->from = $email;
    }

    public function addBCC(string $email, &$name = "Daniel"): void
    {
        $this->bcc = $email;
    }

    public function test(...$params): void
    {
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
            ->paramHasType(0)
            ->paramType(0, "string")
            ->paramDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
            ->count(0);

        $pool->method("test")
            ->paramIsSpread(0) // Same as ->paramIsVariadic()
            ->wrap(function($args) use($inst) {
                echo "World -> $args\n";
            })
            ->count(1);

    }, ["Arg 1"]);

    $mock->test("Hello");
    $service = new UserService($mock);




    // Example 1
    /*



    $inst->validate("yourTestValue", function(ValidatePool $inst) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();
        $inst->isString();
        $inst->isResource();
    });

    $arr = [
        "user" => [
            "name" => "John Doe",
            "email" => "john.doe@gmail.com",
        ]
    ];

    $inst->validate($arr, function(ValidatePool $inst) {
        $inst->validateInData("user.name", "email");
        $inst->validateInData("user.email", "length", [1, 200]);
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


});

