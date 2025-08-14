<?php
declare(strict_types=1);

namespace MaplePHP\Unitary\Config;

use MaplePHP\Emitron\AbstractConfigProps;

/**
 * Defines the set of allowed configuration properties and CLI arguments.
 *
 * CLI arguments with matching property names will override configuration file values.
 *
 * Note:
 * - All properties are nullable, indicating they have not been explicitly set.
 * - Null values allow the system to distinguish between "not provided" and "intentionally set".
 * - Do not use array values or multiple data types
 */
class ConfigProps extends AbstractConfigProps
{
    public ?string $path = null;
    public ?string $exclude = null;
    public ?int $exitCode = null;
    public ?bool $verbose = null;
    public ?bool $errorsOnly = null;
    public ?bool $smartSearch = null;

    /**
     * Hydrate the properties/object with expected data, and handle unexpected data
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function propsHydration(string $key, mixed $value): void
    {
        switch ($key) {
            case 'path':
                $this->path = (!is_string($value) || $value === '') ? null : $value;
                break;
            case 'exclude':
                $this->exclude = (!is_string($value) || $value === '') ? null : $value;
                break;
            case 'exitCode':
                $this->exitCode = ($value === null) ? null : (int)$value;
                break;
            case 'verbose':
                $this->verbose = isset($value) && $value !== false;
                break;
            case 'smartSearch':
                $this->smartSearch = isset($value) && $value !== false;
                break;
            case 'errorsOnly':
                $this->errorsOnly = isset($value) && $value !== false;
                break;
        }
    }

}