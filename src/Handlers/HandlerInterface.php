<?php
/**
 * HandlerInterface — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Handlers;

use MaplePHP\Prompts\Command;

interface HandlerInterface
{
    /**
     * Access the command stream
     * @return Command
     */
    public function getCommand(): Command;

    /**
     * Execute the handler
     * This will automatically be called inside the Unit execution
     * @return void
     */
    public function execute(): void;
}
