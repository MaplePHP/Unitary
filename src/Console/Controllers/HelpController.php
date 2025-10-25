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
                ->addOption("--help", "Display this help message.")
                ->addOption("--show=<hash|name>", "Run a specific test by hash or test name.")
                ->addOption("--errorsOnly", "Show only failing tests, hide passed ones.")
                ->addOption("--path=<path>", "Set test path (absolute or relative).")
                ->addOption("--exclude=<patterns>", "Exclude files or directories (comma-separated, relative to --path).")
                ->addOption("--discoverPattern", "Override test discovery pattern (`tests/` directories or default: `unitary-*.php` files).")
                ->addOption("--smartSearch", "If no tests are found in a subdirectory, Unitary will traverse down to locate tests automatically.")
                ->addOption("--alwaysShowFiles", "Always display full test file paths, even for passing tests.")
                ->addOption("--timezone=<region/city>", "Set default timezone (e.g. `Europe/Stockholm`). Affects date handling.")
                ->addOption("--locale=<locale>", "Set default locale (e.g. `en_US`). Affects date formatting.")
                ->addOption("--verbose", "Show all warnings, including hidden ones.")
                ->addOption("--failFast", "Stop immediately on the first error or exception.")
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
    }
}
