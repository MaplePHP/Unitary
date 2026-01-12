<?php

namespace MaplePHP\Unitary\Interfaces;

interface RouterDispatchInterface
{
    /**
     * Dispatch matched router
     *
     * @param callable $call
     * @return bool
     */
    public function dispatch(callable $call): bool;
}
