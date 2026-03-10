<?php

require_once(__DIR__ . "/TestLib/Mailer.php");
require_once(__DIR__ . "/TestLib/UserService.php");

use MaplePHP\Http\Response;
use MaplePHP\Http\Stream;
use MaplePHP\Unitary\{Config\TestConfig, Expect, Mocker\MethodRegistry, TestCase};
use TestLib\Mailer;
use TestLib\UserService;

$config = TestConfig::make()->withName("mocker");

group($config->withSubject("Wrapper"), function (TestCase $case) {

    $mailer = $case->wrap("TestLib\Mailer");

    $mailer->override("sendEmail", fn(string $email) => "email:{$email}");

    $mailer->add("sendEmail2", function(string $email) use ($mailer) {

        // the method "isValidEmail" is from the original TestLib\Mailer class
        if(!$this->isValidEmail($email)) {
            return false;
        }
        // the method "this" and "sendEmail" the override method above
        return "chained:" . $mailer->this("sendEmail", $email);
    });

    $case->expect($mailer->sendEmail("john@gmail.com"))
        ->isEqualTo("email:john@gmail.com")
        ->validate();

    $case->expect($mailer->sendEmail2("jane@gmail.com"))
        ->isEqualTo("chained:email:jane@gmail.com")
        ->validate();
});

group($config->withSubject("Wrapper2"), function (TestCase $case) {


    $mailer = $case->mock(Mailer::class, function(MethodRegistry $method) {

        // Wrap the mocked method "sendEmail" with new functionality "BUT" with access to
        // the original Mailer class instance
        $method->method("sendEmail")->wrap(function($email) {
            if(!$this->isValidEmail($email)) {
                return "FAILED";
            }
            return "email:{$email}";
        });

        // Tell the mocked "isValidEmail" method to return a specific value
        $method->method("isValidEmail")->willReturn(true);
        // Keep the original method functionality from the Mailer class
        $method->method("getFromEmail")->keepOriginal();
    });

    $case->expect($mailer->sendEmail("john@gmail.com"))
        ->isEqualTo("email:john@gmail.com")
        ->validate();

    $case->expect($mailer->isValidEmail("mocked"))
        ->isTrue()
        ->validate();
});

group($config->withSubject("Can not mock final or private"), function(TestCase $case) {

    $user = $case->mock(UserService::class, function(MethodRegistry $method) {
        $method->method("getUserRole")->willReturn("admin")->called(1);
        $method->method("getUserType")->isFinal()->willReturn("admin");
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

group($config->withSubject("Validating all mocker methods"), function (TestCase $case) {

    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("addFromEmail")
            ->withArguments("john.doe@gmail.com", "John Doe")
            ->withArgumentsForCalls(["john.doe@gmail.com", "John Doe"], ["jane.doe@gmail.com", "Jane Doe"])
            ->willThrowOnce(new InvalidArgumentException("Lorem ipsum"))
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

    $case->expect(function(Expect $inst) use($mail) {
        $inst->expect(fn() => $mail->addFromEmail("john.doe@gmail.com", "John Doe"))->isThrowable(InvalidArgumentException::class);
    });

    $mail->addFromEmail("jane.doe@gmail.com", "Jane Doe");

    $case->error("Test all exception validations")
        ->validate(fn() => throw new ErrorException("Lorem ipsum", 1, 1, "example.php", 22), function(Expect $inst) {
            $inst->isThrowable(ErrorException::class);
            $inst->hasThrowableMessage("Lorem ipsum");
            $inst->hasThrowableSeverity(1);
            $inst->hasThrowableCode(1);
            $inst->hasThrowableFile("example.php");
            $inst->hasThrowableLine(22);
        });

    $case->validate(fn() => throw new TypeError("Lorem ipsum"), function(Expect $inst) {
        $inst->isThrowable(TypeError::class);
    });

    $case->validate(fn() => throw new TypeError("Lorem ipsum"), function(Expect $inst) {
        $inst->isThrowable(function(Expect $inst) {
            $inst->isClass(TypeError::class);
        });
    });
});

group($config->withSubject("Mocking PSR Stream"), function (TestCase $case) {

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


group($config->withSubject("Test partial mocking"), function (TestCase $case) {
    $mail = $case->mock(Mailer::class, function (MethodRegistry $method) {
        $method->method("send")->keepOriginal()->called(1);
        $method->method("isValidEmail")->keepOriginal()->called(1);
        $method->method("sendEmail")->keepOriginal()->called(1);
    });

    $case->validate(fn() => $mail->send(), function(Expect $inst) {
        $inst->hasThrowableMessage("Invalid email");
    });
});


group($config->withSubject("Test immutable PSR Response mocking and default mock values"), function (TestCase $case) {

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
        $inst->isEqualTo("myCustomMockStringValue");
    });

    $case->validate($response->getBody(), function(Expect $inst) {
        $inst->isInstanceOf(Stream::class);
    });

    // You need to return a new instance of TestCase for new mocking settings
    return $case;
});

