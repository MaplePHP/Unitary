<?php

namespace MaplePHP\Unitary\Console\Controllers;

use Psr\Http\Message\ResponseInterface;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Unitary\Renders\SilentRender;
use MaplePHP\Unitary\Support\TestUtils\CodeCoverage;

class CoverageController extends DefaultController
{
    /**
     * Code Coverage Controller
     */
    public function index(RunTestService $service, ResponseInterface $response): ResponseInterface
    {
        if(isset($this->args['help'])) {
            $this->help();
            return $response;
        }

        $coverage = new CodeCoverage();
        $coverage->start();
        $handler = new SilentRender();
        $response = $service->run($handler);
        $coverage->end();

        $result = $coverage->getResponse();
        if ($result !== false) {
            $this->outputBody($result);
        } else {
            $this->command->error("Error: Code coverage is not reachable");
            $this->command->error("Reason: " . $coverage->getIssue()->message());
        }
        $this->command->message("");

        return $response;
    }

    /**
     * Help page for Audit
     *
     * @return void
     */
    public function help(): void
    {
        $blocks = new Blocks($this->command);
        $blocks->addSpace();
        $blocks->addHeadline("--- Unitary Code Coverage Help ---");
        $blocks->addText("Code coverage shows how much of your code was executed during tests, helping you see what’s tested and what isn’t.", "grey");
        $blocks->addSection("Some examples", function (Blocks $inst) {
            return $inst
                ->addExamples(
                    "php vendor/bin/unitary coverage",
                    "Scan project for untested code"
                );
        });
        $blocks->addSpace();
    }

    /**
     * Will output the main body response in CLI
     *
     * @param array $result
     * @return void
     */
    private function outputBody(array $result): void
    {
        $block = new Blocks($this->command);
        $block->addSection("Code coverage", function (Blocks $block) use ($result) {
            return $block->addList("Total lines:", $result['totalLines'])
                ->addList("Executed lines:", $result['executedLines'])
                ->addList("Code coverage percent:", $result['percent'] . "%");
        });
    }
}
