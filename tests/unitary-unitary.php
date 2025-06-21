<?php

require_once(__DIR__ . "/TestLib/Mailer.php");
require_once(__DIR__ . "/TestLib/UserService.php");

use MaplePHP\DTO\Traverse;
use MaplePHP\Http\Response;
use MaplePHP\Http\Stream;
use MaplePHP\Unitary\{Mocker\MethodRegistry, TestCase, TestConfig, Expect, Unit};
use TestLib\Mailer;
use TestLib\UserService;


$unit = new Unit();

//$unit->disableAllTest(false);

$config = TestConfig::make()->withName("unitary");


$unit->group($config->withSubject("Test mocker"), function (TestCase $case) use($unit) {

    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("addFromEmail")
            ->withArguments("john.doe@gmail.com", "John Doe")
            ->called(2);
    });


    $mail->addFromEmail("john.doe@gmail.com", "John Doe");
});


$unit->group("Example of assert in group", function(TestCase $case) {
    assert(1 === 2, "This is a error message");
});

$unit->group($config->withSubject("Can not mock final or private"), function(TestCase $case) {
    $user = $case->mock(UserService::class, function(MethodRegistry $method) {
        $method->method("getUserRole")->willReturn("admin");
        $method->method("getUserType")->willReturn("admin");
    });

    // You cannot mock final with data (should return a warning)
    $case->validate($user->getUserType(), function(Expect $expect) {
        $expect->isEqualTo("guest");
    });

    // You can of course mock regular methods with data
    $case->validate($user->getUserRole(), function(Expect $expect) {
        $expect->isEqualTo("admin");
    });

});

$unit->group($config->withSubject("Test mocker"), function (TestCase $case) use($unit) {

     $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("addFromEmail")
            ->withArguments("john.doe@gmail.com", "John Doe")
            ->withArgumentsForCalls(["john.doe@gmail.com", "John Doe"], ["jane.doe@gmail.com", "Jane Doe"])
            ->willThrowOnce(new InvalidArgumentException("Lowrem ipsum"))
            ->called(2);

        $method->method("addBCC")
            ->isPublic()
            ->hasDocComment()
            ->hasParams()
            ->paramHasType(0)
            ->paramIsType(0, "string")
            ->paramHasDefault(1, "Daniel")
            ->paramIsOptional(1)
            ->paramIsReference(2)
            ->called(0);
    });

    $case->validate(fn() => $mail->addFromEmail("john.doe@gmail.com", "John Doe"), function(Expect $inst) {
        $inst->isThrowable(InvalidArgumentException::class);
    });


    $mail->addFromEmail("jane.doe@gmail.com", "Jane Doe");

    $case->error("Test all exception validations")
        ->validate(fn() => throw new ErrorException("Lorem ipsum", 1, 1, "example.php", 22), function(Expect $inst, Traverse $obj) {
            $inst->isThrowable(ErrorException::class);
            $inst->hasThrowableMessage("Lorem ipsum");
            $inst->hasThrowableSeverity(1);
            $inst->hasThrowableCode(1);
            $inst->hasThrowableFile("example.php");
            $inst->hasThrowableLine(22);
        });

    $case->validate(fn() => throw new TypeError("Lorem ipsum"), function(Expect $inst, Traverse $obj) {
        $inst->isThrowable(TypeError::class);
    });


    $case->validate(fn() => throw new TypeError("Lorem ipsum"), function(Expect $inst, Traverse $obj) {
        $inst->isThrowable(function(Expect $inst) {
            $inst->isClass(TypeError::class);
        });
    });
});

$unit->group($config->withSubject("Mocking response"), function (TestCase $case) use($unit) {

    $stream = $case->mock(Stream::class, function (MethodRegistry $method) {
        $method->method("getContents")
            ->willReturn('HelloWorld', 'HelloWorld2')
            ->calledAtLeast(1);
    });
    $response = new Response($stream);

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->hasResponse();
        $inst->isEqualTo('HelloWorld');
        $inst->notIsEqualTo('HelloWorldNot');
    });

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->isEqualTo('HelloWorld2');
    });
});

$unit->group($config->withSubject("Assert validations"), function ($case) {
    $case->validate("HelloWorld", function(Expect $inst) {
        assert($inst->isEqualTo("HelloWorld")->isValid(), "Assert has failed");
    });
    assert(1 === 1, "Assert has failed");
});

$unit->case($config->withSubject("Old validation syntax"), function ($case) {
    $case->add("HelloWorld", [
        "isString" => [],
        "User validation" => function($value) {
            return $value === "HelloWorld";
        }
    ], "Is not a valid port number");

    $this->add("HelloWorld", [
        "isEqualTo" => ["HelloWorld"],
    ], "Failed to validate");
});

$unit->group($config->withSubject("Validate partial mock"), function (TestCase $case) use($unit) {
    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("send")->keepOriginal();
        $method->method("isValidEmail")->keepOriginal();
        $method->method("sendEmail")->keepOriginal();
    });

    $case->validate(fn() => $mail->send(), function(Expect $inst) {
        $inst->hasThrowableMessage("Invalid email");
    });
});

$unit->group($config->withSubject("Advanced App Response Test"), function (TestCase $case) use($unit) {


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
    $case->getMocker()
        ->mockDataType("string", "myCustomMockStringValue")
        ->mockDataType("array", ["myCustomMockArrayItem"])
        ->mockDataType("int", 200, "getStatusCode");

    $response = $case->buildMock(function (MethodRegistry $method) use($stream) {
        $method->method("getBody")->willReturn($stream);
    });

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->isString();
        $inst->isJson();
    });

    $case->validate($response->getStatusCode(), function(Expect $inst) {
        // Overriding the default making it a 200 integer
        $inst->isHttpSuccess();
    });

    $case->validate($response->getHeader("lorem"), function(Expect $inst) {
        // Overriding the default the array would be ['item1', 'item2', 'item3']
        $inst->isInArray("myCustomMockArrayItem");
    });

    $case->validate($response->getProtocolVersion(), function(Expect $inst) {
        // MockedValue is the default value that the mocked class will return
        $inst->isEqualTo("MockedValue");
    });

    $case->validate($response->getBody(), function(Expect $inst) {
        $inst->isInstanceOf(Stream::class);
    });

    // You need to return a new instance of TestCase for new mocking settings
    return $case;
});


$unit->group($config->withSubject("Testing User service"), function (TestCase $case) {

    $mailer = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("addFromEmail")
            ->keepOriginal()
            ->called(1);
        $method->method("getFromEmail")
            ->keepOriginal()
            ->called(1);
    });

    $service = new UserService($mailer);
    $case->validate($service->registerUser("john.doe@gmail.com"), function(Expect $inst) {
        $inst->isTrue();
    });
});

$unit->group($config->withSubject("Mocking response"), function (TestCase $case) use($unit) {


    $stream = $case->mock(Stream::class, function (MethodRegistry $method) {
        $method->method("getContents")
            ->willReturn('HelloWorld', 'HelloWorld2')
            ->calledAtLeast(1);
    });
    $response = new Response($stream);

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->hasResponse();
        $inst->isEqualTo('HelloWorld');
        $inst->notIsEqualTo('HelloWorldNot');
    });

    $case->validate($response->getBody()->getContents(), function(Expect $inst) {
        $inst->isEqualTo('HelloWorld2');
    });
});

$unit->group("Example API Response", function(TestCase $case) {

    $case->validate('{"response":{"status":200,"message":"ok"}}', function(Expect $expect) {

        $expect->isJson()->hasJsonValueAt("response.status", 404);
        assert($expect->isValid(), "Expected JSON structure did not match.");
    });

});