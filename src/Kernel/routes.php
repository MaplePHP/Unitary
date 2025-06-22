<?php
use MaplePHP\Unitary\Kernel\Controllers\RunTestController;


$router->map(["", "test", "run"], [RunTestController::class, "run"]);
$router->map(["__404", "help"], [RunTestController::class, "help"]);