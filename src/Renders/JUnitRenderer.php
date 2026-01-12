<?php

namespace MaplePHP\Unitary\Renders;

use MaplePHP\DTO\Format\Clock;
use MaplePHP\Unitary\Support\Helpers;
use MaplePHP\Unitary\TestItem;
use MaplePHP\Unitary\TestUnit;
use RuntimeException;
use XMLWriter;

class JUnitRenderer extends AbstractRenderHandler
{
    private XMLWriter $xml;

    /**
     * Pass the main command and stream to handler
     */
    public function __construct(XMLWriter $xml)
    {
        $this->xml = $xml;
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function buildBody(): void
    {
        $className = $this->getClassName();
        $msg = (string)$this->case->getMessage();
        $duration = Helpers::formatDuration($this->case->getDuration());
        $skippedCount = ($this->case->getSkipped() > 0) ? count($this->tests) : 0;

        $this->xml->startElement('testsuite');
        $this->xml->writeAttribute('name', $msg);
        $this->xml->writeAttribute('tests', (string)$this->case->getCount());
        $this->xml->writeAttribute('failures', (string)$this->case->getFailedCount());
        $this->xml->writeAttribute('errors', (string)$this->case->getErrors());
        $this->xml->writeAttribute('skipped', (string)$skippedCount);
        $this->xml->writeAttribute('time', $duration);
        $this->xml->writeAttribute('timestamp', Clock::value("now")->iso());
        $this->xml->writeAttribute('id', $this->checksum);
        if($this->case->getConfig()->select) {
            $this->xml->writeAttribute('name', $this->case->getConfig()->select);
        }

        if ($this->show || $this->alwaysShowFiles || $this->verbose) {
            $this->xml->writeAttribute('file', $this->suitName);
        }

        foreach ($this->tests as $test) {
            if (!($test instanceof TestUnit)) {
                throw new RuntimeException("The @cases (object->array) should return a row with instanceof TestUnit.");
            }
            $caseMsg = str_replace('"', "'", (string)$this->getCaseName($test));
            $this->xml->startElement('testcase');
            $this->xml->writeAttribute('classname', $className);
            $this->xml->writeAttribute('name', $caseMsg);
            $this->xml->writeAttribute('time', $duration);
            if (!$test->isValid()) {
                $trace = $test->getCodeLine();
                //$this->xml->writeAttribute('file', $trace['file']);
                //$this->xml->writeAttribute('line', $trace['line']);
                $errorType = $this->getErrorType($test);
                $type = str_replace('"', "'", $this->getType($test));
                //$errorMsg = ($this->isPHPError($test)) ? "PHP Error" : "Unhandled exception";
                if($test->hasError()) {
                    // IF error has been triggered in validation closure
                    $this->buildErrors($test, $errorType, $type);

                } else if(!$this->case->getConfig()->skip) {
                    foreach ($test->getUnits() as $unit) {
                        /** @var TestItem $unit */
                        if (!$unit->isValid()) {

                            if($this->case->getHasError()) {
                                $this->buildErrors($test, $errorType, $type);

                            } else {
                                //$testMsg = (string)$test->getMessage();
                                $failedMsg = $this->getMessage($test, $unit);
                                $compare = $this->getComparison($unit, $failedMsg);

                                $output = "\n\n";
                                //$output .= ucfirst($errorType) . ": " . ($testMsg !== "" ? $testMsg : $caseMsg) ."\n\n";
                                $output .= "Failed on {$trace['file']}:{$trace['line']}\n";
                                $output .= " → {$trace['code']}\n";
                                $output .= $this->getMessage($test, $unit) . "\n";

                                if($compare !== "") {
                                    $output .= $compare . "\n";
                                }
                                if ($test->hasValue()) {
                                    $output .= "\nInput value: " . $this->getValue($test) . "\n";
                                }

                                $output .= "\n";

                                $validation = $unit->getValidationTitle();
                                $compare = $unit->hasComparison() ? ": " . $unit->getComparison() : "";
                                $compare = str_replace('"', "'", $compare);

                                $this->xml->startElement($errorType);
                                $this->xml->writeAttribute('type',  $type);
                                $this->xml->writeAttribute('message', $this->hasAssertError() ? $this->getAssertMessage() : $validation . $compare);
                                $this->xml->writeCdata($output);
                            }

                            $this->xml->endElement();
                        }
                    }

                }
            }
            $this->xml->endElement();
        }
        $this->xml->endElement();
    }


    /**
     * Build errors
     *
     * @param TestUnit $test
     * @param string $errorType
     * @param string $type
     * @return void
     */
    protected function buildErrors(TestUnit $test, string $errorType, string $type): void
    {
        $errorMsg = ($this->isPHPError($test)) ? "PHP Error" : "Unhandled exception";
        $this->xml->startElement($errorType);
        $this->xml->writeAttribute('type',  $type);
        $this->xml->writeAttribute('message', $errorMsg);
        $this->xml->writeCdata("\n" .$this->getErrorMessage($test));
    }

    /**
     * {@inheritDoc}
     */
    public function buildNotes(): void
    {
        if ($this->outputBuffer) {
            /*
             $lineLength = 80;
            $output = wordwrap($this->outputBuffer, $lineLength);
            $this->xml->startElement('output');
            $this->xml->writeAttribute('message', $output);
            $this->xml->endElement();
             */
        }
    }
}
