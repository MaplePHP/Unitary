<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use Exception;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\TestUtils\CodeCoverage;
use MaplePHP\Unitary\Utils\FileIterator;
use RuntimeException;

class TemplateController extends RunTestController
{

    /**
     * Display a template for the Unitary testing tool
     * Shows a basic template for the Unitary testing tool
     * Only displays if --template argument is provided
     *
     * @param array $args
     * @param Command $command
     * @return void
     */
    public function run(array $args, Command $command): void
    {

        $blocks = new Blocks($command);
        $blocks->addHeadline("\n--- Unitary template ---");
        $blocks->addCode(
            <<<'PHP'
                use MaplePHP\Unitary\{Unit, TestCase, TestConfig, Expect};
                
                $unit = new Unit();
                $unit->group("Your test subject", function (TestCase $case) {
                
                    $case->validate("Your test value", function(Expect $valid) {
                        $valid->isString();
                    });
                    
                });
                PHP
        );
        exit(0);
    }

    /**
     * Main help page
     *
     * @param array $args
     * @param Command $command
     * @return void
     */
    public function help(array $args, Command $command): void
    {
        $blocks = new Blocks($command);
        $blocks->addHeadline("\n--- Unitary Help ---");
        $blocks->addSection("Usage", "php vendor/bin/unitary [options]");

        $blocks->addSection("Options", function(Blocks $inst) {
            return $inst
                ->addOption("help", "Show this help message")
                ->addOption("show=<hash|name>", "Run a specific test by hash or manual test name")
                ->addOption("errors-only", "Show only failing tests and skip passed test output")
                ->addOption("template", "Will give you a boilerplate test code")
                ->addOption("path=<path>", "Specify test path (absolute or relative)")
                ->addOption("exclude=<patterns>", "Exclude files or directories (comma-separated, relative to --path)");
        });

        $blocks->addSection("Examples", function(Blocks $inst) {
            return $inst
                ->addExamples(
                    "php vendor/bin/unitary",
                    "Run all tests in the default path (./tests)"
                )->addExamples(
                    "php vendor/bin/unitary --show=b0620ca8ef6ea7598eaed56a530b1983",
                    "Run the test with a specific hash ID"
                )->addExamples(
                    "php vendor/bin/unitary --errors-only",
                    "Run all tests in the default path (./tests)"
                )->addExamples(
                    "php vendor/bin/unitary --show=YourNameHere",
                    "Run a manually named test case"
                )->addExamples(
                    "php vendor/bin/unitary --template",
                    "Run a and will give you template code for a new test"
                )->addExamples(
                    'php vendor/bin/unitary --path="tests/" --exclude="tests/legacy/*,*/extras/*"',
                    'Run all tests under "tests/" excluding specified directories'
                );
        });
        // Make sure nothing else is executed when help is triggered
        exit(0);
    }

}