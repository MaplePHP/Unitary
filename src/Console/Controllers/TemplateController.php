<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Prompts\Themes\Blocks;

class TemplateController extends DefaultController
{
    /**
     * Display a template for the Unitary testing tool
     * Shows a basic template for the Unitary testing tool
     * Only displays if --template argument is provided
     *
     * @return void
     */
    public function index(): void
    {
        $blocks = new Blocks($this->command);
        $blocks->addHeadline("\n--- Copy and paste code --->");
        $blocks->addCode(
            <<<'PHP'
                use MaplePHP\Unitary\{Expect,TestCase};
                
                group("Your test subject", function (TestCase $case) {
                
                    $case->expect("YourValue")
                        ->isString()
                        ->validate();
                });
                PHP
        );
        $blocks->addHeadline("---------------------------\n");
    }
}