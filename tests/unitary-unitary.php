
<?php

use MaplePHP\Http\Request;
use MaplePHP\Http\Response;
use MaplePHP\Http\Stream;
use MaplePHP\Unitary\{TestCase, TestConfig, Expect, Unit};

class Mailer
{
    public $from = "";
    public $bcc = "";


    public function __construct()
    {

    }

    public function send()
    {
        echo $this->sendEmail($this->getFromEmail());
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

    public function setFromEmail(string $email): self
    {
        $this->from = $email;
        return $this;
    }

    public function getFromEmail(): string
    {
        return !empty($this->from) ? $this->from : "empty email";
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
        $this->test2();
    }

    public function test2(): void
    {
        echo "Hello World\n";
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


$config = TestConfig::make("This is a test message")
    ->setSkip()
    ->setSelect('unitary');

$unit->group($config, function (TestCase $case) use($unit) {

    $request = new Request("HSHHS", "https://example.com:443/?cat=25&page=1622");

    $case->validate($request->getMethod(), function(Expect $inst) {
        $inst->isRequestMethod();
    });

    $case->validate($request->getPort(), function(Expect $inst) {
        $inst->isEqualTo(443);
    });

    $case->validate($request->getUri()->getQuery(), function(Expect $inst) {
        $inst->hasQueryParam("cat");
        $inst->hasQueryParam("page", 1622);
    });
});



$unit->group("Advanced App Response Test", function (TestCase $case) use($unit) {

    $stream = $case->mock(Stream::class);
    $response = new Response($stream);

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->hasResponse();
    });
});
/*




$unit->group("Advanced Mailer Test", function (TestCase $case) use($unit) {
    $mail = $case->mock(Mailer::class, function (MethodPool $pool) {
        $pool->method("send")->keepOriginal();
        $pool->method("sendEmail")->keepOriginal();
    });
    $mail->send();
});

$unit->group("Advanced App Response Test", function (TestCase $case) use($unit) {


    // Quickly mock the Stream class
    $stream = $case->mock(Stream::class, function (MethodPool $pool) {
        $pool->method("getContents")
            ->willReturn('{"test":"test"}')
            ->calledAtLeast(1);

        $pool->method("fopen")->isPrivate();
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
        $pool->method("getBody")->willReturn($stream);
    });


    $case->validate($response->getBody()->getContents(), function(Validate $inst, Traverse $collection) {
        $inst->isString();
        $inst->isJson();
        return $collection->strJsonDecode()->test->valid("isString");
    });

    $case->validate($response->getHeader("lorem"), function(Validate $inst) {
        // Validate against the new default array item value
        // If we weren't overriding the default the array would be ['item1', 'item2', 'item3']
        $inst->isInArray(["myCustomMockArrayItem"]);
    });

    $case->validate($response->getStatusCode(), function(Validate $inst) {
        // Will validate to the default int data type set above
        // and bounded to "getStatusCode" method
        $inst->isHttpSuccess();
    });

    $case->validate($response->getProtocolVersion(), function(Validate $inst) {
        // MockedValue is the default value that the mocked class will return
        // if you do not specify otherwise, either by specify what the method should return
        // or buy overrides the default mocking data type values.
        $inst->isEqualTo("MockedValue");
    });

    $case->validate($response->getBody(), function(Validate $inst) {
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
            ->called(1);

        //$pool->method("test2")->called(1);
    });
    $mock->addBCC("World");
    $mock->test(1);
});


//$unit = new Unit();
$unit->group("Unitary test 2", function (TestCase $inst) {

    $mock = $inst->mock(Mailer::class, function (MethodPool $pool) use($inst) {
        $pool->method("addFromEmail")
            ->isPublic();

        $pool->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
            ->called(0);

        $pool->method("test")
            ->hasParams()
            ->paramIsSpread(0) // Same as ->paramIsVariadic()
            ->wrap(function($args) use($inst) {
                echo "World -> $args\n";
            })
            ->called(1);

        $pool->method("test2")
            ->hasNotParams()
            ->called(0);

    }, ["Arg 1"]);

    //$mock->test("Hello");
    //$service = new UserService($mock);

    $inst->validate("yourTestValue", function(Validate $inst) {
        $inst->isBool();
        $inst->isInt();
        $inst->isJson();
        $inst->isString();
        $inst->isResource();
    });

});

 */