
<?php

use MaplePHP\DTO\Traverse;
use MaplePHP\Http\Request;
use MaplePHP\Http\Response;
use MaplePHP\Http\Stream;
use MaplePHP\Unitary\{Mocker\MethodRegistry, Mocker\MockedMethod, TestCase, TestConfig, Expect, Unit};

class Mailer
{
    public $from = "";
    public $bcc = "";


    public function __construct()
    {

    }

    public function send()
    {
        $this->sendEmail($this->getFromEmail());
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

    /**
     * Add a BCC (blind carbon copy) email address
     *
     * @param string $email The email address to be added as BCC
     * @param string $name The name associated with the email address, default is "Daniel"
     * @param mixed $testRef A reference variable, default is "Daniel"
     * @return void
     */
    public function addBCC(string $email, string $name = "Daniel", &$testRef = "Daniel"): void
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

    public function registerUser(string $email): bool {
        // register user logic...

        $this->mailer->addFromEmail($email);
        $this->mailer->addBCC("jane.doe@hotmail.com", "Jane Doe");
        $this->mailer->addBCC("lane.doe@hotmail.com", "Lane Doe");

        if(!filter_var($this->mailer->getFromEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email");
        }
        //echo $this->mailer->sendEmail($email, $name)."\n";
        //echo $this->mailer->sendEmail($email, $name);
        return true;
    }
}

$unit = new Unit();

$config = TestConfig::make("Testing mocking library")->setName("unitary");

$unit->group($config, function (TestCase $case) use($unit) {

    $stream = $case->mock(Stream::class, function (MethodRegistry $method) {
        $method->method("getContents")
            ->willReturn('')
            ->calledAtLeast(1);
    });
    $response = new Response($stream);

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->hasResponse();
    });


    $stream = $case->mock(Stream::class);
    $response = new Response($stream);
    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->hasResponse();
    });
});

$unit->group($config->setMessage("Testing custom validations"), function ($case) {

    $case->validate("HelloWorld", function(Expect $inst) {
        assert($inst->isEqualTo("HelloWorld")->isValid(), "Assert has failed");
    });

    assert(1 === 1, "Assert has failed");

});

$unit->case($config->setMessage("Validate old Unitary case syntax"), function ($case) {

    $case->add("HelloWorld", [
        "isString" => [],
        "User validation" => function($value) {
            return $value === "HelloWorld";
        }
    ], "Is not a valid port number");

    $this->add("HelloWorld", [
        "isEqualTo" => ["HelloWorld"],
    ], "Failed to validate");;
});

/*


$unit->group("Advanced Mailer Test", function (TestCase $case) use($unit) {
    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("send")->keepOriginal();
        $method->method("sendEmail")->keepOriginal();
    });
    $mail->send();
});

$unit->group("Advanced App Response Test", function (TestCase $case) use($unit) {


    // Quickly mock the Stream class
    $stream = $case->mock(Stream::class, function (MethodRegistry $method) {
        $method->method("getContents")
            ->willReturn('{"test":"test"}')
            ->calledAtLeast(1);

        $method->method("fopen")->isPrivate();
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

    $response = $case->buildMock(function (MethodRegistry $method) use($stream) {
        // Even tho Unitary mocker tries to automatically mock the return type of methods,
        // it might fail if the return type is an expected Class instance, then you will
        // need to manually set the return type to tell Unitary mocker what class to expect,
        // which is in this example a class named "Stream".
        // You can do this by either passing the expected class directly into the `return` method
        // or even better by mocking the expected class and then passing the mocked class.
        $method->method("getBody")->willReturn($stream);
    });


    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->isString();
        $inst->isJson();
    });

    $case->validate($response->getHeader("lorem"), function(Expect $inst) {
        // Validate against the new default array item value
        // If we weren't overriding the default the array would be ['item1', 'item2', 'item3']
        $inst->isInArray(["myCustomMockArrayItem"]);
    });

    $case->validate($response->getStatusCode(), function(Expect $inst) {
        // Will validate to the default int data type set above
        // and bounded to "getStatusCode" method
        $inst->isHttpSuccess();
    });

    $case->validate($response->getProtocolVersion(), function(Expect $inst) {
        // MockedValue is the default value that the mocked class will return
        // if you do not specify otherwise, either by specify what the method should return
        // or buy overrides the default mocking data type values.
        $inst->isEqualTo("MockedValue");
    });

    $case->validate($response->getBody(), function(Expect $inst) {
        $inst->isInstanceOf(Stream::class);
    });

    // You need to return a new instance of TestCase for new mocking settings
    return $case;
});


$unit->group("Mailer test", function (TestCase $inst) use($unit) {
    $mock = $inst->mock(Mailer::class, function (MethodRegistry $method) use($inst) {
        $method->method("addBCC")
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
            ->called(1);
    });
    $mock->addBCC("World");
    $mock->test(1);
});


$unit->group("Testing User service", function (TestCase $inst) {

    $mailer = $inst->mock(Mailer::class, function (MethodRegistry $method) use($inst) {
        $method->method("addFromEmail")
            ->called(1);

        $method->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(1)
            ->called(2);

        $method->method("getFromEmail")
            ->willReturn("john.doe@gmail.com");

    }, [true // <- Mailer class constructor argument, enable debug]);

    $service = new UserService($mailer);

    $case->validate($service->send(), function(Expect $inst) {
        $inst->isTrue();
    });

});
$unit->group("Testing User service", function (TestCase $case) {

    $mailer = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("addFromEmail")
            ->called(1);

        $method->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "string")
            ->paramHasDefault(2, "Daniel")
            ->paramIsOptional(2)
            ->paramIsReference(2)
            ->called(1);

        $method->method("getFromEmail")
            ->willReturn("john.doe@gmail.com");

    }, [true]); // <-- true is passed as argument 1 to Mailer constructor

    $service = new UserService($mailer);
    $case->validate($service->registerUser("john.doe@gmail.com"), function(Expect $inst) {
        $inst->isTrue();
    });

});
 */




