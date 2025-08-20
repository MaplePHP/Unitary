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

use ErrorException;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Interfaces\TestEmitterInterface;
use RuntimeException;
use Throwable;

class TestEmitter implements TestEmitterInterface
{
    protected array $args = [];
    protected Unit $unit;

    public function __construct(BodyInterface $handler, AbstractHandlerInterface $errorHandler, array $args)
    {
        $this->unit = new Unit($handler);
        $this->args = $args;
        $this->runBlunder($errorHandler);
    }

    /**
     * @throws Throwable
     * @throws BlunderErrorException
     * @throws ErrorException
     */
    public function emit(string $file): void
    {

        $verbose = (bool)($this->args['verbose'] ?? false);

        if (!is_file($file)) {
            throw new RuntimeException("The test file \"$file\" do not exists.");
        }

        require_once($file);

        $hasExecutedTest = $this->unit->execute();

        if (!$hasExecutedTest && $verbose) {
            trigger_error(
                "Could not find any tests inside the test file:\n" .
                $file . "\n\n" .
                "Possible causes:\n" .
                "  • There are no test in test group/case.\n" .
                "  • Unitary could not locate the Unit instance.\n" .
                "  • You did not use the `group()` function.\n" .
                "  • You created a new Unit in the test file but did not return it at the end.",
                E_USER_WARNING
            );
        }

    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    /**
     * Initialize Blunder error handler
     *
     * @param AbstractHandlerInterface $errorHandler
     * @return void
     */
    protected function runBlunder(AbstractHandlerInterface $errorHandler): void
    {
        $run = new Run($errorHandler);
        $run->setExitCode(1);
        $run->load();
    }
}
