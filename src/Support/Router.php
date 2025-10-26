<?php

/**
 * Unit — Part of the MaplePHP Unitary CLI Router
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */

declare(strict_types=1);

namespace MaplePHP\Unitary\Support;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use MaplePHP\Unitary\Interfaces\RouterInterface;

class Router implements RouterInterface
{
    private array $controllers = [];
    private string $needle;
    private ?array $mapId = null;
    private array $args;
    private array $middlewares = [];

    public function __construct(string $needle, array $args)
    {
        $this->args = $args;
        $this->needle = $needle;
    }

    /**
     * Map one or more needles to controller
     *
     * @param string|array $needles
     * @param array $controller
     * @param array $args Pass custom data to router
     * @return $this
     */
    public function map(string|array $needles, array $controller, array $args = []): self
    {
        $inst = clone $this;
        if (isset($args['handler'])) {
            throw new InvalidArgumentException('The handler argument is reserved, you can not use that key.');
        }

        if (is_string($needles)) {
            $needles = [$needles];
        }
        $inst->mapId = $needles;
        foreach ($inst->mapId as $key) {
            $inst->controllers[$key] = [
                "handler" => $controller,
                ...$args
            ];
        }
        return $inst;
    }

    /**
     * Add Middleware to Router
     *
     * @param MiddlewareInterface|string $middleware
     * @return $this
     */
    public function with(MiddlewareInterface|string $middleware): self
    {
        if($this->mapId === null) {
            throw new \BadMethodCallException('You need to map a route before calling the with method.');
        }
        $inst = clone $this;
        foreach ($inst->mapId as $key) {
            $inst->middlewares[$key][] = $middleware;
        }
        $this->mapId = null;
        return $inst;
    }

    /**
     * Dispatch matched router
     *
     * @param callable $call
     * @return bool
     */
    public function dispatch(callable $call): bool
    {
        if (isset($this->controllers[$this->needle])) {
            $call($this->controllers[$this->needle], $this->args, ($this->middlewares[$this->needle] ?? []), $this->needle);
            return true;
        }
        if (isset($this->controllers["__404"])) {
            $call($this->controllers["__404"], $this->args, ($this->middlewares[$this->needle] ?? []), $this->needle);
        }
        return false;
    }
}
