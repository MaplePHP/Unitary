<?php

/**
 * TestConfig — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary;

use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\Interfaces\TestEmitterInterface;

class TestEmitter implements TestEmitterInterface {

    protected string $file;
    protected Unit $unit;


    public function __construct(string $file)
    {
        if(!is_file($file)) {
            throw new \RuntimeException("The test file \"$file\" do not exists.");
        }
        $this->unit = new Unit();
        $this->file = $file;
    }

    public function emit(): void
    {
        $this->runBlunder();
        require_once($this->file);
        $this->unit->execute();
    }

    /**
     * Initialize Blunder error handler
     *
     * @return void
     */
    protected function runBlunder(): void
    {
        $run = new Run(new CliHandler());
        $run->setExitCode(1);
        $run->load();
    }
}