<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use Exception;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Utils\FileIterator;
use RuntimeException;

class RunTestController extends DefaultController
{

    /**
     * Main test runner
     *
     * @param array $args
     * @param Command $command
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(array $args, Command $command): void
    {
        $defaultPath = $this->container->get("request")->getUri()->getDir();
        try {
            $path = ($args['path'] ?? $defaultPath);
            if(!isset($path)) {
                throw new RuntimeException("Path not specified: --path=path/to/dir");
            }
            $testDir = realpath($path);
            if(!is_dir($testDir)) {
                throw new RuntimeException("Test directory '$testDir' does not exist");
            }
            $unit = new FileIterator($args);
            $unit->executeAll($testDir, $defaultPath);

        } catch (Exception $e) {
            $command->error($e->getMessage());
        }
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