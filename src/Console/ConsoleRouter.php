<?php

use MaplePHP\Unitary\Console\Controllers\CoverageController;
use MaplePHP\Unitary\Console\Controllers\HelpController;
use MaplePHP\Unitary\Console\Controllers\RunTestController;
use MaplePHP\Unitary\Console\Controllers\TemplateController;

// Bind Middleware to router with `with(TestMiddleware::class)`
// use MaplePHP\Unitary\Console\Middlewares\TestMiddleware;
// $router->map(["", "test", "run"], [RunTestController::class, "run"])->with(TestMiddleware::class)

return $router
    ->map("coverage", [CoverageController::class, "run"])
    ->map("template", [TemplateController::class, "run"])
    ->map("junit", [RunTestController::class, "runJUnit"])
    ->map(["", "test", "run"], [RunTestController::class, "run"])
    ->map(["__404", "help"], [HelpController::class, "index"]);
