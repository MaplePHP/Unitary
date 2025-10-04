<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Renders\JUnitRenderer;

class HelpController extends DefaultController
{

    /**
     * Main help page
     *
     * @return void
     */
    public function index(): void
    {
        $blocks = new Blocks($this->command);
        $blocks->addHeadline("\n--- Unitary Help ---");
        $blocks->addSection("Usage", "php vendor/bin/unitary [function] [options]");

        $blocks->addSection("Options", function (Blocks $inst) {
            return $inst
                ->addOption("--help", "Show this help message")
                ->addOption("--show=<hash|name>", "Run a specific test by hash or manual test name")
                ->addOption("--errorsOnly", "Show only failing tests and skip passed test output")
                ->addOption("--path=<path>", "Specify test path (absolute or relative)")
                ->addOption("--exclude=<patterns>", "Exclude files or directories (comma-separated, relative to --path)")
                ->addOption("--smartSearch", "If no test is found in sub-directory then Unitary will try to traverse back and auto find tests.")
                ;
        });


        $blocks->addSection("Function list", function (Blocks $inst) {
            return $inst
                ->addOption("template", "Will give you a boilerplate test code")
                ->addOption("coverage", "Will show you a how much code this is used");
        });


        $blocks->addSection("Examples", function (Blocks $inst) {
            return $inst
                ->addExamples(
                    "php vendor/bin/unitary",
                    "Run all tests in the default path (./tests)"
                )->addExamples(
                    "php vendor/bin/unitary run",
                    "Same as above"
                )->addExamples(
                    "php vendor/bin/unitary --show=b0620ca8ef6ea7598e5ed56a530b1983",
                    "Run the test with a specific hash ID"
                )->addExamples(
                    "php vendor/bin/unitary --show=YourNameHere",
                    "Run a manually named test case"
                )->addExamples(
                    "php vendor/bin/unitary coverage",
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
    protected function buildFooter(): void
    {
        $inst = TestDiscovery::getUnitaryInst();
        if ($inst !== null) {
            $dot = $this->command->getAnsi()->middot();
            $peakMemory = (string)round(memory_get_peak_usage() / 1024, 2);

            $this->command->message(
                $this->command->getAnsi()->style(
                    ["italic", "grey"],
                    "Total tests: " . $inst::getPassedTests() . "/" . $inst::getTotalTests() . " $dot " .
                    "Errors: " . $inst::getTotalErrors() . " $dot " .
                    "Peak memory usage: " . $peakMemory . " KB"
                )
            );
            $this->command->message("");
        }

    }

}
