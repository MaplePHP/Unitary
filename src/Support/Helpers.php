<?php
/**
 * Helpers — Part of the MaplePHP Unitary Testing Library
 *
 * @package:    MaplePHP\Unitary
 * @author:     Daniel Ronkainen
 * @licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
 *              Don't delete this comment, it's part of the license.
 */
declare(strict_types=1);

namespace MaplePHP\Unitary\Support;

use ErrorException;
use Exception;
use MaplePHP\DTO\Format\Str;

final class Helpers
{

    /**
     * Used to stringify arguments to show in a test
     *
     * @param mixed $args
     * @return string
     */
    public static function stringifyArgs(mixed $args): string
    {
        $levels = 0;
        $str = self::stringify($args, $levels);
        if($levels > 1) {
            return "[$str]";
        }
        return $str;
    }

    /**
     * Stringify an array and objects
     *
     * @param mixed $arg
     * @param int $levels
     * @return string
     */
    public static function stringify(mixed $arg, int &$levels = 0): string
    {
        if (is_array($arg)) {
            $items = array_map(function($item) use(&$levels) {
                $levels++;
                return self::stringify($item, $levels);
            }, $arg);
            return implode(', ', $items);
        }

        if (is_object($arg)) {
            return get_class($arg);
        }

        return (string)$arg;
    }

    /**
     * Create a file instead of eval for improved debug
     *
     * @param string $filename
     * @param string $input
     * @return void
     * @throws Exception
     */
    public static function createFile(string $filename, string $input): void
    {
        $temp = getenv('UNITARY_TEMP_DIR');
        $tempDir = $temp !== false ? $temp : sys_get_temp_dir();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempFile = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($tempFile, "<?php\n" . $input);

        if(!is_file($tempFile)) {
            throw new Exception("Unable to create file $tempFile");
        }
        include $tempFile;

        /*
        register_shutdown_function(function () use ($tempFile) {
            unlink($tempFile);
        });
         */
    }

    /**
     * Processes a trace array to retrieve specific details about the file, line, and code context.
     *
     * @param array $trace The trace array containing details such as file and line.
     * @return array An associative array with keys 'line', 'file', and 'code' representing the line number, file path, and contextual code respectively.
     * @throws ErrorException
     */
    public static function getTrace(array $trace): array
    {
        $codeLine = [];
        $file = (string)($trace['file'] ?? '');
        $line = (int)($trace['line'] ?? 0);
        $lines = file($file);
        $code = "";
        if($lines !== false) {
            $code = trim($lines[$line - 1] ?? '');
            if (str_starts_with($code, '->')) {
                $code = substr($code, 2);
            }
            $code = self::excerpt($code);
        }

        $codeLine['line'] = $line;
        $codeLine['file'] = $file;
        $codeLine['code'] = $code;

        return $codeLine;
    }


    /**
     * Generates an excerpt from the given string with a specified maximum length.
     *
     * @param string $value The input string to be excerpted.
     * @param int $length The maximum length of the excerpt. Defaults to 80.
     * @return string The resulting excerpted string.
     * @throws ErrorException
     */
    final public static function excerpt(string $value, int $length = 80): string
    {
        $format = new Str($value);
        return (string)$format->excerpt($length)->get();
    }

    /**
     * Used to get a readable value (Move to utility)
     *
     * @param mixed|null $value
     * @param bool $minify
     * @return string
     * @throws ErrorException
     */
    public static function stringifyDataTypes(mixed $value = null, bool $minify = false): string
    {
        if (is_bool($value)) {
            return '"' . ($value ? "true" : "false") . '"' . ($minify ? "" : " (type: bool)");
        }
        if (is_int($value)) {
            return '"' . self::excerpt((string)$value) . '"' . ($minify ? "" : " (type: int)");
        }
        if (is_float($value)) {
            return '"' . self::excerpt((string)$value) . '"' . ($minify ? "" : " (type: float)");
        }
        if (is_string($value)) {
            return '"' . self::excerpt($value) . '"' . ($minify ? "" : " (type: string)");
        }
        if (is_array($value)) {
            $json = json_encode($value);
            if($json === false) {
                return "(unknown type)";
            }
            return '"' . self::excerpt($json) . '"' . ($minify ? "" : " (type: array)");
        }
        if (is_callable($value)) {
            return '"' . self::excerpt(get_class((object)$value)) . '"' . ($minify ? "" : " (type: callable)");
        }
        if (is_object($value)) {
            return '"' . self::excerpt(get_class($value)) . '"' . ($minify ? "" : " (type: object)");
        }
        if ($value === null) {
            return '"null"'. ($minify ? '' : ' (type: null)');
        }
        if (is_resource($value)) {
            return '"' . self::excerpt(get_resource_type($value)) . '"' . ($minify ? "" : " (type: resource)");
        }

        return "(unknown type)";
    }
}