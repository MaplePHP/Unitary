<?php
declare(strict_types=1);

namespace MaplePHP\Unitary;

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
    public ?int $exitCode = null;
    public ?bool $verbose = null;
    public ?bool $smartSearch = null;
}