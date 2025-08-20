<?php

/**
 * MethodRegistry — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Mocker;

use BadMethodCallException;

class MethodRegistry
{
    private ?MockBuilder $mocker;
    /** @var array<string, array<string, MockedMethod>> */
    private static array $methods = [];

    public function __construct(?MockBuilder $mocker = null)
    {
        $this->mocker = $mocker;
    }

    /**
     * @param string $class
     * @return void
     */
    public static function reset(string $class): void
    {
        self::$methods[$class] = [];
    }

    /**
     * Access method pool
     * @param string $class
     * @param string $name
     * @return MockedMethod|null
     */
    public static function getMethod(string $class, string $name): ?MockedMethod
    {
        $mockedMethod = self::$methods[$class][$name] ?? null;
        if ($mockedMethod instanceof MockedMethod) {
            return $mockedMethod;
        }
        return null;
    }

    /**
     * This method adds a new method to the pool with a given name and
     * returns the corresponding MethodItem instance.
     *
     * @param string $name The name of the method to add.
     * @return MockedMethod The newly created MethodItem instance.
     */
    public function method(string $name): MockedMethod
    {
        if (is_null($this->mocker)) {
            throw new BadMethodCallException("MockBuilder is not set yet.");
        }
        self::$methods[$this->mocker->getMockedClassName()][$name] = new MockedMethod($this->mocker);
        return self::$methods[$this->mocker->getMockedClassName()][$name];
    }

    /**
     * Get method
     *
     * @param string $key
     * @return MockedMethod|null
     */
    public function get(string $key): MockedMethod|null
    {
        if (is_null($this->mocker)) {
            throw new BadMethodCallException("MockBuilder is not set yet.");
        }
        return self::$methods[$this->mocker->getMockedClassName()][$key] ?? null;
    }

    /**
     * Get all methods
     *
     * @return array True if the method exists, false otherwise.
     */
    public function getAll(): array
    {
        return self::$methods;
    }

    /**
     * Checks if a method with the given name exists in the pool.
     *
     * @param string $name The name of the method to check.
     * @return bool True if the method exists, false otherwise.
     */
    public function has(string $name): bool
    {
        if (is_null($this->mocker)) {
            throw new BadMethodCallException("MockBuilder is not set yet.");
        }
        return isset(self::$methods[$this->mocker->getMockedClassName()][$name]);
    }

    public function getSelected(array $names): array
    {
        if (is_null($this->mocker)) {
            throw new BadMethodCallException("MockBuilder is not set yet.");
        }

        return array_filter($names, fn ($name) => $this->has($name));
    }

}
