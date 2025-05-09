<?php

namespace MaplePHP\Unitary\Mocker;

/**
 * A controller class responsible for managing mock data for methods.
 * Provides methods to add, retrieve, and track mock data, including support for singleton access.
 */
final class MockerController extends MethodPool
{
    private static ?MockerController $instance = null;
    /** @var array<string, array<string, object>> */
    private static array $data = [];

    /**
     * Get a singleton instance of MockerController
     * Creates a new instance if none exists
     *
     * @return static The singleton instance of MockerController
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
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
        $data = isset(self::$data[$mockIdentifier]) ? self::$data[$mockIdentifier] : false;
        if (!is_array($data)) {
            return false;
        }
        return $data;
    }

    /**
     * Get specific data item by mock identifier and method name
     *
     * @param string $mockIdentifier The identifier of the mock
     * @param string $method The method name to retrieve
     * @return mixed Returns the data item if found, false otherwise
     */
    public static function getDataItem(string $mockIdentifier, string $method): mixed
    {
        return self::$data[$mockIdentifier][$method] ?? false;
    }
    
    /**
     * Add or update data for a specific mock method
     *
     * @param string $mockIdentifier The identifier of the mock
     * @param string $method The method name to add data to
     * @param string $key The key of the data to add
     * @param mixed $value The value to add
     * @return void
     */
    public static function addData(string $mockIdentifier, string $method, string $key, mixed $value): void
    {
        if (isset(self::$data[$mockIdentifier][$method])) {
            self::$data[$mockIdentifier][$method]->{$key} = $value;
        }
    }

    /**
     * Builds and manages method data for mocking
     * Decodes JSON method string and handles mock data storage with count tracking
     *
     * @param string $method JSON string containing mock method data
     * @return object Decoded method data object with updated count if applicable
     */
    public function buildMethodData(string $method, bool $isBase64Encoded = false): object
    {
        $method = $isBase64Encoded ? base64_decode($method) : $method;
        $data = (object)json_decode($method);
        if (isset($data->mocker) && isset($data->name)) {
            $mocker = (string)$data->mocker;
            $name = (string)$data->name;
            if (empty(self::$data[$mocker][$name])) {
                $data->called = 0;
                self::$data[$mocker][$name] = $data;
                // Mocked method has trigger "once"!
            } else {
                if (isset(self::$data[$mocker][$name])) {
                    self::$data[$mocker][$name]->called = (int)self::$data[$mocker][$name]->called + 1;
                    // Mocked method has trigger "More Than" once!
                }
            }
        }
        return $data;
    }

}
