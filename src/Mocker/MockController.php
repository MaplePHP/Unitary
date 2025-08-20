<?php

/**
 * MockController — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Mocker;

/**
 * A controller class responsible for managing mock data for methods.
 * Provides methods to add, retrieve, and track mock data, including support for singleton access.
 */
final class MockController extends MethodRegistry
{
    private static ?MockController $instance = null;
    /** @var array<string, array<string, object>> */
    private static array $data = [];

    /**
     * Get a singleton instance of MockController
     * Creates a new instance if none exists
     *
     * @return static The singleton instance of MockController
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
        $data = self::$data[$mockIdentifier] ?? false;
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
    public function buildMethodData(string $method, array $args = [], bool $isBase64Encoded = false): object
    {
        $method = $isBase64Encoded ? base64_decode($method) : $method;
        $data = (object)json_decode($method);

        if (isset($data->mocker) && isset($data->name)) {
            $mocker = (string)$data->mocker;
            $name = (string)$data->name;
            if (empty(self::$data[$mocker][$name])) {
                // This is outside the mocked method
                // You can prepare values here with defaults
                $data->called = 0;
                $data->arguments = [];
                $data->throw = null;
                self::$data[$mocker][$name] = $data;
                // Mocked method has trigger "once"!
            } else {
                // This is the mocked method
                // You can overwrite the default with the expected mocked values here
                if (isset(self::$data[$mocker][$name])) {
                    /** @psalm-suppress MixedArrayAssignment */
                    self::$data[$mocker][$name]->arguments[] = $args;
                    self::$data[$mocker][$name]->called = (int)self::$data[$mocker][$name]->called + 1;
                    // Mocked method has trigger "More Than" once!
                }
            }
        }
        return $data;
    }

}
