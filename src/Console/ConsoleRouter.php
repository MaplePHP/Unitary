<?php

use MaplePHP\Unitary\Console\Controllers\CoverageController;
use MaplePHP\Unitary\Console\Controllers\RunTestController;
use MaplePHP\Unitary\Console\Controllers\TemplateController;

$router->map("coverage", [CoverageController::class, "run"]);
$router->map("template", [TemplateController::class, "run"]);
$router->map(["", "test", "run"], [RunTestController::class, "run"]);
$router->map(["__404", "help"], [RunTestController::class, "help"]);
