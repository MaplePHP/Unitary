
<?php

use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\Unit;
use MaplePHP\Validate\ValidationChain;
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
    public function addFromEmail(string $email, string $name = ""): void
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

    public function test2(): void
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
$unit->group("Unitary test 2", function (TestCase $inst) use($unit) {
     $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addBCC")
            ->isAbstract()
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(0)
            ->paramIsReference(1)
            ->count(1);

         $pool->method("test")
             ->count(1);
    });
    $mock->addBCC("World");
});

/*

$unit = new Unit();
$unit->group("Unitary test 2", function (TestCase $inst) {

    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addFromEmail")
            ->hasParamsTypes()
            ->isPublic()
            ->hasDocComment()
            ->hasReturnType()
            ->count(0);

        $pool->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
            ->count(0);

        $pool->method("test")
            ->hasParams()
            ->paramIsSpread(0) // Same as ->paramIsVariadic()
            ->wrap(function($args) use($inst) {
                echo "World -> $args\n";
            })
            ->count(1);

        $pool->method("test2")
            ->hasNotParams()
            ->count(0);

    }, ["Arg 1"]);

    $mock->test("Hello");
    $service = new UserService($mock);

    $validPool = new ValidationChain("dwqdqw");
    $validPool
        ->isEmail()
        ->length(1, 200)
        ->endsWith(".com");
    $isValid = $validPool->isValid();

    $inst->validate("yourTestValue", function(ValidationChain $inst) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();
        $inst->isString();
        $inst->isResource();
    });

});

*/

