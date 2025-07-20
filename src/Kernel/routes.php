<?php

use MaplePHP\Unitary\Kernel\Controllers\CoverageController;
use MaplePHP\Unitary\Kernel\Controllers\RunTestController;
use MaplePHP\Unitary\Kernel\Controllers\TemplateController;


$router->map("coverage", [CoverageController::class, "run"]);
$router->map("template", []);
$router->map(["", "test", "run"], [RunTestController::class, "run"]);
$router->map(["__404", "help"], [RunTestController::class, "help"]);