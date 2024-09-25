<?php
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