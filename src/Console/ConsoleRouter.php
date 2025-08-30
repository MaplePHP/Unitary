<?php

use MaplePHP\Unitary\Console\Controllers\CoverageController;
use MaplePHP\Unitary\Console\Controllers\RunTestController;
use MaplePHP\Unitary\Console\Controllers\TemplateController;
use MaplePHP\Unitary\Console\Middlewares\TestMiddleware;

return $router
    ->map("coverage", [CoverageController::class, "run"])
    ->map("template", [TemplateController::class, "run"])
    ->map("junit", [RunTestController::class, "runJUnit"])
    ->map(["", "test", "run"], [RunTestController::class, "run"])->with(TestMiddleware::class)
    ->map(["__404", "help"], [RunTestController::class, "help"]);
