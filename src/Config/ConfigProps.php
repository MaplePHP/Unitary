<?php

declare(strict_types=1);

namespace MaplePHP\Unitary\Config;

use InvalidArgumentException;
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
    public ?string $discoverPattern = null;
    public ?string $exclude = null;
    public ?string $show = null;
    public ?string $timezone = null;
    public ?string $local = null;
    public ?int $exitCode = null;
    public ?bool $verbose = null;
    public ?bool $alwaysShowFiles = null;
    public ?bool $errorsOnly = null;
    public ?bool $smartSearch = null;
    public ?bool $failFast = null;


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
            case 'discoverPattern':
                $this->discoverPattern = (!is_string($value) || $value === '') ? null : $value;
                break;
            case 'exclude':
                $this->exclude = (!is_string($value) || $value === '') ? null : $value;
                break;
            case 'show':
                $this->show = (!is_string($value) || $value === '') ? null : $value;
                break;
            case 'timezone':
                // The default timezone is 'CET'
                $this->timezone = (!is_string($value) || $value === '') ? 'Europe/Stockholm' : $value;
                break;
            case 'local':
                // The default timezone is 'CET'
                $this->local = (!is_string($value) || $value === '') ? 'en_US' : $value;
                if(!$this->isValidLocale($this->local)) {
                    throw new InvalidArgumentException(
                        "Invalid locale '{$this->local}'. Expected format like 'en_US' (language_COUNTRY)."
                    );
                }
                break;
            case 'exitCode':
                $this->exitCode = ($value === null) ? null : (int)$value;
                break;
            case 'verbose':
                $this->verbose = $this->dataToBool($value);
                break;
            case 'alwaysShowFiles':
                $this->alwaysShowFiles = $this->dataToBool($value);
                break;
            case 'smartSearch':
                $this->smartSearch = $this->dataToBool($value);
                break;
            case 'errorsOnly':
                $this->errorsOnly = $this->dataToBool($value);
                break;
            case 'failFast':
                $this->failFast = $this->dataToBool($value);
                break;
        }
    }

}
