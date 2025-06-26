<?php

namespace MaplePHP\Unitary\TestUtils;

use MaplePHP\Prompts\Command;

class Configs
{

    private static ?self $instance = null;
    private Command $command;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setCommand(Command $command): void
    {
        self::getInstance()->command = $command;
    }

    public function getCommand(): Command
    {
        return self::getInstance()->command;
    }

}
