<?php

namespace MaplePHP\Unitary\Contracts;

interface RouterDispatchInterface
{
    /**
     * Dispatch matched router
     *
     * @param callable $call
     * @return bool
     */
    function dispatch(callable $call): bool;
}