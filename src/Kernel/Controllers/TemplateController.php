<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use Exception;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\TestUtils\CodeCoverage;
use MaplePHP\Unitary\Utils\FileIterator;
use RuntimeException;

class TemplateController extends DefaultController
{

    /**
     * Display a template for the Unitary testing tool
     * Shows a basic template for the Unitary testing tool
     * Only displays if --template argument is provided
     *
     * @return void
     */
    public function run(): void
    {
        $blocks = new Blocks($this->command);
        $blocks->addHeadline("\n--- Copy and paste code --->");
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
        $blocks->addHeadline("---------------------------\n");
        exit(0);
    }


}