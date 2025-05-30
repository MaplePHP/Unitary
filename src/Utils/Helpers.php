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

namespace MaplePHP\Unitary\Utils;

final class Helpers
{

    /**
     * Used to stringify arguments to show in test
     *
     * @param array $args
     * @return string
     */
    public static function stringifyArgs(array $args): string
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
     */
    public static function createFile(string $filename, string $input)
    {
        $temp = getenv('UNITARY_TEMP_DIR');
        $tempDir = $temp !== false ? $temp : sys_get_temp_dir();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempFile = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($tempFile, "<?php\n" . $input);

        if(!is_file($tempFile)) {
            throw new \Exception("Unable to create file $tempFile");
        }
        include $tempFile;

        /*
        register_shutdown_function(function () use ($tempFile) {
            unlink($tempFile);
        });
         */
    }

}