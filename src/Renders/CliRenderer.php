<?php

namespace MaplePHP\Unitary\Renders;

use MaplePHP\Prompts\Command;
use MaplePHP\Unitary\TestItem;
use MaplePHP\Unitary\TestUnit;
use MaplePHP\Unitary\Support\Helpers;
use RuntimeException;

class CliRenderer extends AbstractRenderHandler
{
    private Command $command;
    private string $color;
    private string $flag;

    /**
     * Pass the main command and stream to handler
     *
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBody(): void
    {
        $this->initDefault();

        $this->command->message("");
        $this->command->message(
            $this->flag . " " .
            $this->command->getAnsi()->style(["bold"], $this->formatFileTitle($this->suitName)) .
            " - " .
            $this->command->getAnsi()->style(["bold", $this->color], (string)$this->case->getMessage())
        );

        if($this->show && !$this->case->hasFailed()) {
            $this->command->message("");
            $this->command->message(
                $this->command->getAnsi()->style(["italic", $this->color], "Test file: " . $this->suitName)
            );
        }

        if (($this->show || !$this->case->getConfig()->skip)) {

            // Show possible warnings
            if($this->case->getWarning()) {
                $this->command->message("");
                $this->command->message(
                    $this->command->getAnsi()->style(["italic", "yellow"], $this->case->getWarning())
                );
            }

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
        if($this->outputBuffer) {
            $lineLength = 80;
            $output = wordwrap($this->outputBuffer, $lineLength);
            $line = $this->command->getAnsi()->line($lineLength);

            $this->command->message("");
            $this->command->message($this->command->getAnsi()->style(["bold"], "Note:"));
            $this->command->message($line);
            $this->command->message($output);
            $this->command->message($line);
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
        $this->command->message("");

        $passed = $this->command->getAnsi()->bold("Passed: ");
        if ($this->case->getHasAssertError()) {
            $passed .= $this->command->getAnsi()->style(["grey"], "N/A");
        } else {
            $passed .= $this->command->getAnsi()->style([$this->color], $this->case->getCount() . "/" . $this->case->getTotal());
        }

        $footer = $passed .
            $this->command->getAnsi()->style(["italic", "grey"], " - ". $select);
        if (!$this->show && $this->case->getConfig()->skip) {
            $footer = $this->command->getAnsi()->style(["italic", "grey"], $select);
        }
        $this->command->message($footer);
        $this->command->message("");

    }

    /**
     * Failed tests template part
     *
     * @return void
     * @throws \ErrorException
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
                    $this->command->message("");
                    $this->command->message(
                        $this->command->getAnsi()->style(["bold", $this->color], "Error: ") .
                        $this->command->getAnsi()->bold($msg)
                    );
                    $this->command->message("");

                    $trace = $test->getCodeLine();
                    if (!empty($trace['code'])) {
                        $this->command->message($this->command->getAnsi()->style(["bold", "grey"], "Failed on {$trace['file']}:{$trace['line']}"));
                        $this->command->message($this->command->getAnsi()->style(["grey"], " → {$trace['code']}"));
                    }

                    foreach ($test->getUnits() as $unit) {

                        /** @var TestItem $unit */
                        if (!$unit->isValid()) {
                            $lengthA = $test->getValidationLength();
                            $validation = $unit->getValidationTitle();
                            $title = str_pad($validation, $lengthA);
                            $compare = $unit->hasComparison() ? $unit->getComparison() : "";

                            $failedMsg = "   " .$title . " → failed";
                            $this->command->message($this->command->getAnsi()->style($this->color, $failedMsg));

                            if ($compare) {
                                $lengthB = (strlen($compare) + strlen($failedMsg) - 8);
                                $comparePad = str_pad($compare, $lengthB, " ", STR_PAD_LEFT);
                                $this->command->message(
                                    $this->command->getAnsi()->style($this->color, $comparePad)
                                );
                            }
                        }
                    }
                    if ($test->hasValue()) {
                        $this->command->message("");
                        $this->command->message(
                            $this->command->getAnsi()->bold("Input value: ") .
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
        $this->flag = $this->command->getAnsi()->style(['blueBg', 'brightWhite'], " PASS ");
        if ($this->case->hasFailed()) {
            $this->flag = $this->command->getAnsi()->style(['redBg', 'brightWhite'], " FAIL ");
        }
        if ($this->case->getConfig()->skip) {
            $this->color = "yellow";
            $this->flag = $this->command->getAnsi()->style(['yellowBg', 'black'], " SKIP ");
        }
    }
}