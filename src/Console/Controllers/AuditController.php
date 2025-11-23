<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Unitary\Console\Services\AuditService;
use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Unitary\Support\Helpers;
use Psr\Http\Message\ResponseInterface;
use MaplePHP\Unitary\Renders\JUnitRenderer;

class AuditController extends DefaultController
{

    public function dependencyCheck(AuditService $service)
    {

        $data = $service->dependencyCheck();
        $total = count($data);

        foreach($data as $row) {
            $this->command->message("{$row['package']} version {$row['version']}");
        }

        $this->command->message("Total packages: {$total}");
    }

    /**
     * Main test runner
     */
    public function index(AuditService $service): void
    {


        print_r($service->dependencyCheck());
        die;

        $service->rrrr();






        die("wwww");
    }


}
