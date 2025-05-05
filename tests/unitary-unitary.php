
<?php

use MaplePHP\DTO\Traverse;
use MaplePHP\Unitary\TestCase;
use MaplePHP\Unitary\Unit;
use MaplePHP\Validate\ValidationChain;
use MaplePHP\Unitary\Mocker\MethodPool;
use MaplePHP\Http\Response;
use MaplePHP\Http\Stream;


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


$unit->group("Advanced App Response Test", function (TestCase $case) use($unit) {

    $stream = $case->mock(Stream::class);
    $response = new Response($stream);

    $case->validate($response->getBody()->getContents(), function(ValidationChain $inst) {
        $inst->hasResponse();
    });
});


$unit->group("Advanced App Response Test", function (TestCase $case) use($unit) {


    // Quickly mock the Stream class
    $stream = $case->mock(Stream::class, function (MethodPool $pool) {
        $pool->method("getContents")
            ->return('{"test":"test"}');
    });

    // Mock with configuration
    //
    // Notice: this will handle TestCase as immutable, and because of this
    // the new instance of TestCase must be return to the group callable below
    //
    // By passing the mocked Stream class to the Response constructor, we
    // will actually also test that the argument has the right data type
    $case = $case->withMock(Response::class, [$stream]);

    // We can override all "default" mocking values tide to TestCase Instance
    // to use later on in out in the validations, you can also tie the mock
    // value to a method
    $case->getMocker()
        ->mockDataType("string", "myCustomMockStringValue")
        ->mockDataType("array", ["myCustomMockArrayItem"])
        ->mockDataType("int", 200, "getStatusCode");

    // List all default mock values that will be automatically used in
    // parameters and return values
    //print_r(\MaplePHP\Unitary\TestUtils\DataTypeMock::inst()->getMockValues());

    $response = $case->buildMock(function (MethodPool $pool) use($stream) {
        // Even tho Unitary mocker tries to automatically mock the return type of methods,
        // it might fail if the return type is an expected Class instance, then you will
        // need to manually set the return type to tell Unitary mocker what class to expect,
        // which is in this example a class named "Stream".
        // You can do this by either passing the expected class directly into the `return` method
        // or even better by mocking the expected class and then passing the mocked class.
        $pool->method("getBody")->return($stream);
    });


    $case->validate($response->getBody()->getContents(), function(ValidationChain $inst, Traverse $collection) {
        $inst->isString();
        $inst->isJson();
        return $collection->strJsonDecode()->test->valid("isString");
    });

    $case->validate($response->getHeader("lorem"), function(ValidationChain $inst) {
        // Validate against the new default array item value
        // If we weren't overriding the default the array would be ['item1', 'item2', 'item3']
        $inst->isInArray(["myCustomMockArrayItem"]);
    });

    $case->validate($response->getStatusCode(), function(ValidationChain $inst) {
        // Will validate to the default int data type set above
        // and bounded to "getStatusCode" method
        $inst->isHttpSuccess();
    });

    $case->validate($response->getProtocolVersion(), function(ValidationChain $inst) {
        // MockedValue is the default value that the mocked class will return
        // if you do not specify otherwise, either by specify what the method should return
        // or buy overrides the default mocking data type values.
        $inst->isEqualTo("MockedValue");
    });

    $case->validate($response->getBody(), function(ValidationChain $inst) {
        $inst->isInstanceOf(Stream::class);
    });

    // You need to return a new instance of TestCase for new mocking settings
    return $case;
});


$unit->group("Mailer test", function (TestCase $inst) use($unit) {
    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addBCC")
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
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

