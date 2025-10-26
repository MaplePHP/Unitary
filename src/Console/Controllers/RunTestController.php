<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use Psr\Http\Message\ResponseInterface;
use MaplePHP\Unitary\Renders\JUnitRenderer;

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
     * Main test runner
     */
    public function runJUnit(RunTestService $service): ResponseInterface
    {
        $suites = new \XMLWriter();
        $suites->openMemory();
        $suites->setIndent(true);
        $suites->setIndentString("    ");
        $handler  = new JUnitRenderer($suites);
        $response = $service->run($handler);

        // 2) Get the suites XML fragment
        $suitesXml = $suites->outputMemory();

        // Duration: pick your source (internal timer is fine)
        $inst = TestDiscovery::getUnitaryInst();
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString("    ");
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('testsuites');
        $xml->writeAttribute('tests',    (string)$inst::getTotalTests());
        $xml->writeAttribute('failures', (string)$inst::getTotalFailed());
        $xml->writeAttribute('errors',   (string)$inst::getTotalErrors());
        $xml->writeAttribute('time',     (string)$inst::getDuration(6));
        // Optional: $xml->writeAttribute('skipped', (string)$totalSkipped);

        $xml->writeRaw($suitesXml);

        $xml->endElement();
        $xml->endDocument();

        $response->getBody()->write($xml->outputMemory());
        return $response;
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
