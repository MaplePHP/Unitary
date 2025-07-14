<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use Exception;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\TestUtils\Configs;
use MaplePHP\Unitary\Utils\FileIterator;
use RuntimeException;

class RunTestController extends DefaultController
{

    /**
     * Main test runner
     *
     * @return FileIterator
     */
    public function run(): FileIterator
    {
        $iterator = new FileIterator($this->args);
        return $this->iterateTest($this->command, $iterator, $this->args);
    }

    /**
     * @param Command $command
     * @param FileIterator $iterator
     * @param array $args
     * @return FileIterator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \MaplePHP\Blunder\Exceptions\BlunderSoftException
     */
    protected function iterateTest(Command $command, FileIterator $iterator, array $args): FileIterator
    {
        Configs::getInstance()->setCommand($command);

        $defaultPath = $this->container->get("request")->getUri()->getDir();
        $path = ($args['path'] ?? $defaultPath);
        if(!isset($path)) {
            throw new RuntimeException("Path not specified: --path=path/to/dir");
        }
        $testDir = realpath($path);


        if(!file_exists($testDir)) {
            throw new RuntimeException("Test directory '$testDir' does not exist");
        }

        $iterator->enableExitScript(false);
        $iterator->executeAll($testDir, $defaultPath);
        return $iterator;
    }

    /**
     * Main help page
     *
     * @param array $args
     * @param Command $command
     * @return void
     */
    public function help(): void
    {
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