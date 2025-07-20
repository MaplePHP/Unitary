<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Kernel\DispatchConfig;
use MaplePHP\Unitary\TestUtils\CodeCoverage;
use MaplePHP\Unitary\Utils\FileIterator;
use Psr\Container\NotFoundExceptionInterface;

class CoverageController extends RunTestController
{

    /**
     * Main test runner
     *
     * @return void
     * @throws BlunderSoftException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(): void
    {

        /** @var DispatchConfig $config */
        $config = $this->container->get("dispatchConfig");

        // Create a silent handler
        $coverage = new CodeCoverage();
        $commandInMem = new Command(new Stream(Stream::TEMP));
        $iterator = new FileIterator($this->args);
        $config->setExitCode($iterator->getExitCode());

        $coverage->start();
        $this->iterateTest($commandInMem, $iterator, $this->args);
        $coverage->end();

        $result = $coverage->getResponse();

        if($result !== false) {
            $block = new Blocks($this->command);
            $block->addSection("Code coverage", function(Blocks $block) use ($result) {
                return $block->addList("Total lines:", $result['totalLines'])
                    ->addList("Executed lines:", $result['executedLines'])
                    ->addList("Code coverage percent:", $result['percent']);
            });

        } else {
            $this->command->error("Error: Code coverage is not reachable");
            $this->command->error("Reason: " . $coverage->getIssue()->message());
        }

        $this->command->message("");
    }

    /**
     * Main help page
     *
     * @return void
     */
    public function help(): void
    {
        die();
        $blocks = new Blocks($this->command);
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