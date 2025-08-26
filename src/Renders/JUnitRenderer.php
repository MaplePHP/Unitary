<?php

namespace MaplePHP\Unitary\Renders;

use ErrorException;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\TestItem;
use MaplePHP\Unitary\TestUnit;
use MaplePHP\Unitary\Support\Helpers;
use RuntimeException;
use XMLWriter;

class JUnitRenderer extends AbstractRenderHandler
{
    private XMLWriter $xml;
    private string $color;
    private string $flag;

    /**
     * Pass the main command and stream to handler
     */
    public function __construct(XMLWriter $xml)
    {
        $this->xml = $xml;
    }

    /**
     * {@inheritDoc}
     * @throws ErrorException
     */
    public function buildBody(): void
    {


        $this->xml->startElement('testsuite');

        $this->xml->writeAttribute('name', $this->formatFileTitle($this->suitName) . " - " . (string)$this->case->getMessage());
        $this->xml->writeAttribute('tests', (string)$this->case->getCount());
        $this->xml->writeAttribute('failures', (string)$this->case->getFailedCount());
        $this->xml->writeAttribute('errors', (string)$this->case->getErrors());
        $this->xml->writeAttribute('skipped', (string)$this->case->getSkipped());

        var_dump($this->case->getCount());
        var_dump($this->case->getFailedCount());
        var_dump($this->case->getErrors());
        var_dump(Clock::value("now")->dateTime());
        die;
        if (($this->show || !$this->case->getConfig()->skip)) {
            // Show possible warnings
            /*
             if ($this->case->getWarning()) {
                $this->xml->message("");
                $this->xml->message(
                    $this->xml->getAnsi()->style(["italic", "yellow"], $this->case->getWarning())
                );
            }
             */

            // Show Failed tests
            $this->showFailedTests();
        }

        $this->showFooter();
    }

    /**
     * {@inheritDoc}
     */
    public function buildNotes(): void
    {
        if ($this->outputBuffer) {
            $lineLength = 80;
            $output = wordwrap($this->outputBuffer, $lineLength);
            $line = $this->xml->getAnsi()->line($lineLength);

            $this->xml->message("");
            $this->xml->message($this->xml->getAnsi()->style(["bold"], "Note:"));
            $this->xml->message($line);
            $this->xml->message($output);
            $this->xml->message($line);
        }
    }

    /**
     * Footer template part
     *
     * @return void
     */
    protected function showFooter(): void
    {
        $select = $this->checksum;
        if ($this->case->getConfig()->select) {
            $select .= " (" . $this->case->getConfig()->select . ")";
        }
        $this->xml->message("");

        $passed = $this->xml->getAnsi()->bold("Passed: ");
        if ($this->case->getHasAssertError()) {
            $passed .= $this->xml->getAnsi()->style(["grey"], "N/A");
        } else {
            $passed .= $this->xml->getAnsi()->style([$this->color], $this->case->getCount() . "/" . $this->case->getTotal());
        }

        $footer = $passed .
            $this->xml->getAnsi()->style(["italic", "grey"], " - ". $select);
        if (!$this->show && $this->case->getConfig()->skip) {
            $footer = $this->xml->getAnsi()->style(["italic", "grey"], $select);
        }
        $this->xml->message($footer);
        $this->xml->message("");

    }

    /**
     * Failed tests template part
     *
     * @return void
     * @throws ErrorException
     */
    protected function showFailedTests(): void
    {
        if (($this->show || !$this->case->getConfig()->skip)) {
            foreach ($this->tests as $test) {

                if (!($test instanceof TestUnit)) {
                    throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestUnit.");
                }

                if (!$test->isValid()) {
                    $msg = (string)$test->getMessage();
                    $this->xml->message("");
                    $this->xml->message(
                        $this->xml->getAnsi()->style(["bold", $this->color], "Error: ") .
                        $this->xml->getAnsi()->bold($msg)
                    );
                    $this->xml->message("");

                    $trace = $test->getCodeLine();
                    if (!empty($trace['code'])) {
                        $this->xml->message($this->xml->getAnsi()->style(["bold", "grey"], "Failed on {$trace['file']}:{$trace['line']}"));
                        $this->xml->message($this->xml->getAnsi()->style(["grey"], " → {$trace['code']}"));
                    }

                    foreach ($test->getUnits() as $unit) {

                        /** @var TestItem $unit */
                        if (!$unit->isValid()) {
                            $lengthA = $test->getValidationLength();
                            $validation = $unit->getValidationTitle();
                            $title = str_pad($validation, $lengthA);
                            $compare = $unit->hasComparison() ? $unit->getComparison() : "";

                            $failedMsg = "   " .$title . " → failed";
                            $this->xml->message($this->xml->getAnsi()->style($this->color, $failedMsg));

                            if ($compare) {
                                $lengthB = (strlen($compare) + strlen($failedMsg) - 8);
                                $comparePad = str_pad($compare, $lengthB, " ", STR_PAD_LEFT);
                                $this->xml->message(
                                    $this->xml->getAnsi()->style($this->color, $comparePad)
                                );
                            }
                        }
                    }
                    if ($test->hasValue()) {
                        $this->xml->message("");
                        $this->xml->message(
                            $this->xml->getAnsi()->bold("Input value: ") .
                            Helpers::stringifyDataTypes($test->getValue())
                        );
                    }
                }
            }
        }
    }

    /**
     * Init some default styled object
     *
     * @return void
     */
    protected function initDefault(): void
    {
        $this->color = ($this->case->hasFailed() ? "brightRed" : "brightBlue");
        $this->flag = $this->xml->getAnsi()->style(['blueBg', 'brightWhite'], " PASS ");
        if ($this->case->hasFailed()) {
            $this->flag = $this->xml->getAnsi()->style(['redBg', 'brightWhite'], " FAIL ");
        }
        if ($this->case->getConfig()->skip) {
            $this->color = "yellow";
            $this->flag = $this->xml->getAnsi()->style(['yellowBg', 'black'], " SKIP ");
        }
    }
}
