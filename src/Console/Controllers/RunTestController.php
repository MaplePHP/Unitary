<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Prompts\Themes\Blocks;

class RunTestController extends DefaultController
{

    /**
     * Main test runner
     */
    public function run(RunTestService $service): ResponseInterface
    {
        $handler = new CliRenderer($this->command);
        $response = $service->run($handler);
        $this->buildFooter();
        return $response;
    }

    /**
     * Main help page
     *
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
                ->addOption("errorsOnly", "Show only failing tests and skip passed test output")
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
                    "php vendor/bin/unitary --errorsOnly",
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

    /**
     * Create a footer showing and end of script command
     *
     * This is not really part of the Unit test library, as other stuff might be present here
     *
     * @return void
     */
    protected function buildFooter()
    {
        $inst = TestDiscovery::getUnitaryInst();
        if($inst !== null) {
            $dot = $this->command->getAnsi()->middot();
            $peakMemory = (string)round(memory_get_peak_usage() / 1024, 2);

            $this->command->message(
                $this->command->getAnsi()->style(
                    ["italic", "grey"],
                    "Total: " . $inst->getPassedTests() . "/" . $inst->getTotalTests() . " $dot " .
                    "Peak memory usage: " . $peakMemory . " KB"
                )
            );
            $this->command->message("");
        }

    }

}