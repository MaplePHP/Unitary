<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\DTO\Format\Clock;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Console\Services\AuditService;
use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Renders\CliRenderer;
use MaplePHP\Unitary\Console\Services\RunTestService;
use MaplePHP\Unitary\Support\Helpers;
use Psr\Http\Message\ResponseInterface;
use MaplePHP\Unitary\Renders\JUnitRenderer;

class AuditController extends DefaultController
{


    /**
     * Audit CLI index
     *
     * @param AuditService $service
     * @return void
     */
    public function index(AuditService $service): void
    {
        switch ($this->props->type) {
            case "supply-chain":
            case "dependencies":
                $this->supplyChain($service);
                break;
            default;
                $this->vulnerabilityAudit($service);
                break;
        }
    }

    /**
     * Package Vulnerability Audit
     */
    public function vulnerabilityAudit(AuditService $service): void
    {
        $cveData = $service->getSeverities();
        $severityCount = count($cveData);
        $blocks = new Blocks($this->command);

        $this->command->message("");
        $blocks->addHeadline("Package Vulnerability Audit");

        if ($severityCount > 0) {
            $this->command->message("");
            foreach ($cveData as $row) {
                $blocks->addRow(function (Blocks $inst) use ($row) {
                    return $inst
                        ->addCell("Title", $row['title'])
                        ->addCell("Package", $row['package'])
                        ->addCell("Version", $row['version'])
                        ->addCell("Severity", $inst->severityLevel($row['severity']->value))
                        ->addCell("CVE ID", $row['id'])
                        ->addCell("Link", $row['link']);
                });
            }

            $this->command->message(
                $this->command->getAnsi()->style(['bold', 'brightRed'], "{$severityCount} vulnerabilities detected")
            );

            $this->command->message(
                $this->command->getAnsi()->italic("Update affected packages to resolve the issues.")
            );
            $this->command->message("");

        } else {
            $blocks->addText("All dependencies appear secure.", "green");
            $this->command->message("");
        }
    }


    /**
     * List all dependencies that you are using through composer
     *
     * @param AuditService $service
     * @return void
     */
    public function supplyChain(AuditService $service)
    {
        $data = $service->dependencyCheck();
        $total = count($data);

        $this->command->message("");
        $blocks = new Blocks($this->command);
        $blocks->addHeadline("Supply chain audit");

        $blocks->addSection("Installed composer packages", function (Blocks $inst) use ($data) {
            foreach ($data as $row) {
                $inst = $inst->addList($row['package'], "v{$row['version']}");
            }
            return $inst;
        });

        $this->command->message($this->command->getAnsi()->bold("Total packages: ") . $total);
        $this->command->message("");
    }

}
