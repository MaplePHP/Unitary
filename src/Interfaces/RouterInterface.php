<?php

namespace MaplePHP\Unitary\Interfaces;

interface RouterInterface extends RouterDispatchInterface
{
    /**
     * Map one or more needles to controller
     *
     * Note: Map will not map to an HTTP method as a map is just what is needed,
     * this is because a CLI router, for example, does not need it.
     *
     * @param string|array $needles
     * @param array $controller
     * @param array $args
     * @return $this
     */
    public function map(string|array $needles, array $controller, array $args = []): self;
}
