<?php

namespace MaplePHP\Unitary\Kernel\Controllers;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Kernel\DispatchConfig;
use MaplePHP\Unitary\TestUtils\CodeCoverage;
use MaplePHP\Unitary\Utils\FileIterator;

class CoverageController extends RunTestController
{

    /**
     * Main test runner
     *
     */
    public function ruwn(ResponseInterface $response): ResponseInterface
    {

        /** @var DispatchConfig $config */
        /*
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
         */
        return $response;
    }
}