<?php

namespace MaplePHP\Unitary\Interfaces;

use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;

interface TestEmitterInterface
{

    public function emit(string $file): void;
}