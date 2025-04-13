<?php

namespace MaplePHP\Unitary\Mocker;

class MethodPool
{
    private ?Mocker $mocker = null;
    private array $methods = [];

    public function __construct(?Mocker $mocker = null)
    {
        $this->mocker = $mocker;
    }

    /**
     * This method adds a new method to the pool with a given name and
     * returns the corresponding MethodItem instance.
     *
     * @param string $name The name of the method to add.
     * @return MethodItem The newly created MethodItem instance.
     */
    public function method(string $name): MethodItem
    {
        $this->methods[$name] = new MethodItem($this->mocker);
        return $this->methods[$name];
    }

    /**
     * Get method
     *
     * @param string $key
     * @return MethodItem|null
     */
    public function get(string $key): MethodItem|null
    {
        return $this->methods[$key] ?? null;
    }

    /**
     * Get all methods
     *
     * @return array True if the method exists, false otherwise.
     */
    public function getAll(): array
    {
        return $this->methods;
    }

    /**
     * Checks if a method with the given name exists in the pool.
     *
     * @param string $name The name of the method to check.
     * @return bool True if the method exists, false otherwise.
     */
    public function has(string $name): bool
    {
        return isset($this->methods[$name]);
    }

}