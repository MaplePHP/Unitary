<?php

namespace MaplePHP\Unitary\Mocker;

class MockerController extends MethodPool
{
    private static ?MockerController $instance = null;

    private static array $data = [];

    private array $methods = [];

    public static function getInstance(): self
    {
        if(is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the method information
     *
     * @param string $mockIdentifier
     * @return array|bool
     */
    public static function getData(string $mockIdentifier): array|bool
    {
        return (self::$data[$mockIdentifier] ?? false);
    }

    public static function getDataItem(string $mockIdentifier, string $method): mixed
    {
        return self::$data[$mockIdentifier][$method];
    }

    public static function addData(string $mockIdentifier, string $method, string $key, mixed $value): void
    {
        self::$data[$mockIdentifier][$method]->{$key} = $value;
    }

    public function buildMethodData(string $method): object
    {
        $data = json_decode($method);
        if(empty(self::$data[$data->mocker][$data->name])) {
            $data->count = 0;
            self::$data[$data->mocker][$data->name] = $data;
        } else {
            self::$data[$data->mocker][$data->name]->count++;
        }
        return $data;
    }

}