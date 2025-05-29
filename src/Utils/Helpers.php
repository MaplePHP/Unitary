<?php

namespace MaplePHP\Unitary\Utils;

class Helpers
{

    public static function createFile(string $filename, string $input)
    {
        $tempDir = getenv('UNITARY_TEMP_DIR') ?: sys_get_temp_dir();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempFile = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($tempFile, "<?php\n" . $input);
        include $tempFile;

        /*
        register_shutdown_function(function () use ($tempFile) {
            unlink($tempFile);
        });
         */
    }

}