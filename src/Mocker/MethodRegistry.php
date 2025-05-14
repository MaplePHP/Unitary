<?php

namespace MaplePHP\Unitary\Mocker;

class MethodRegistry
{
    private ?MockBuilder $mocker = null;
    /** @var array<string, MockedMethod> */
    private static array $methods = [];

    public function __construct(?MockBuilder $mocker = null)
    {
        $this->mocker = $mocker;
    }

    /**
     * Access method pool
     * @param string $class
     * @param string $name
     * @return MockedMethod|null
     */
    public static function getMethod(string $class, string $name): ?MockedMethod
    {
        return self::$methods[$class][$name] ?? null;
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
        self::$methods[$this->mocker->getClassName()][$name] = new MockedMethod($this->mocker);
        return self::$methods[$this->mocker->getClassName()][$name];
    }

    /**
     * Get method
     *
     * @param string $key
     * @return MockedMethod|null
     */
    public function get(string $key): MockedMethod|null
    {
        return self::$methods[$this->mocker->getClassName()][$key] ?? null;
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
        return isset(self::$methods[$this->mocker->getClassName()][$name]);
    }

}
