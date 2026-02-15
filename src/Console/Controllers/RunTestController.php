<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Unitary\Support\Helpers;
use Psr\Http\Message\ResponseInterface;
use MaplePHP\Unitary\Renders\JUnitRenderer;

class RunTestController extends DefaultController
{

    /**
     * Main test runner
     */
    public function index(RunTestService $service): ResponseInterface
    {
        switch ($this->props->type)
        {
            case "junit": case "xml":
                return $this->runJUnit($service);
            default;
                if(!($this->props->type === null || $this->props->type === "cli" || $this->props->type === "default")) {
                    throw new BlunderSoftException(
                        "Unsupported argument type value: \"{$this->props->type}\". See the help documentation for details."
                    );
                }
                return $this->runCli($service);
        }
    }

    /**
     * Main test runner
     */
    protected function runCli(RunTestService $service): ResponseInterface
    {
        $handler = new CliRenderer($this->command);
        $response = $service->run($handler);
        $this->buildFooter();
        return $response;
    }

    /**
     * Main test runner
     */
    protected function runJUnit(RunTestService $service): ResponseInterface
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
        $inst = TestDiscovery::getUnitaryInst(true);
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString("    ");
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('testsuites');
        $xml->writeAttribute('tests', (string)$inst::getTotalTests());
        $xml->writeAttribute('failures', (string)$inst::getTotalFailed());
        $xml->writeAttribute('errors', (string)$inst::getTotalErrors());
        $xml->writeAttribute('time', Helpers::formatDuration($inst::getTotalDuration()));
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
     * @throws \Exception
     */
    protected function buildFooter(): void
    {
        $inst = TestDiscovery::getUnitaryInst(true);
        $dot = $this->command->getAnsi()->middot();
        $this->command->message($this->command->getAnsi()->line(80));
        $this->command->message(
            $this->command->getAnsi()->style(
                ["bold", $inst::isSuccessful() ? "green" : "red"],
                "\nTests: " . $inst::getTotalTests() . " $dot " .
                "Failures: " . $inst::getTotalFailed() . " $dot " .
                "Errors: " . $inst::getTotalErrors() . " $dot " .
                "Skipped: " . $inst::getTotalSkipped() . " \n"
            )
        );
        $this->command->message(
            $this->command->getAnsi()->style(
                ["italic", "grey"],
                "Duration: " . Helpers::formatDuration($inst::getTotalDuration()) . " seconds $dot " .
                "Memory: " . Helpers::byteToMegabyte($inst::getTotalMemory()) . " MB $dot " .
                "Date: " . Clock::value("now")->iso() . "\n"
            )
        );
        $this->command->message($this->command->getAnsi()->line(80));
        $this->command->message("");
    }
}
