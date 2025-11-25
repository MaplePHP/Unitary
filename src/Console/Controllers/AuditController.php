<?php

namespace MaplePHP\Unitary\Console\Controllers;

use MaplePHP\Http\Exceptions\NetworkException;
use MaplePHP\Http\Exceptions\RequestException;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Console\Services\AuditService;

class AuditController extends DefaultController
{
    /**
     * Audit CLI index to trigger dependencies or security scanner
     *
     * @param AuditService $service
     * @return void
     * @throws NetworkException
     * @throws RequestException
     */
    public function index(AuditService $service): void
    {
        switch ($this->props->type) {
            case "supply-chain":
            case "dependencies":
                $this->supplyChain($service);
                break;
            case "security":
            case "severities":
            case "vulnerabilities":
            case "cve":
                $this->vulnerabilityAudit($service);
                break;
            default:
                $this->help();
                break;
        }
    }

    /**
     * Help page for Audit
     *
     * @return void
     */
    public function help(): void
    {
        $blocks = new Blocks($this->command);
        $blocks->addHeadline("\n--- Unitary Audit Help ---");

        $blocks->addSection("Options", function (Blocks $inst) {
            return $inst->addOption("--type", "security, dependencies");
        });

        $blocks->addSection("Some examples", function (Blocks $inst) {
            return $inst
                ->addExamples(
                    "php vendor/bin/unitary audit --type=security",
                    "Scan project for vulnerabilities (CVE scan)"
                )->addExamples(
                    "php vendor/bin/unitary run --type=dependencies",
                    "Inspect dependency tree and supply-chain footprint."
                );
        });
        $blocks->addSpace();
    }

    /**
     * Package Vulnerability Audit
     *
     * @param AuditService $service
     * @return void
     * @throws NetworkException
     * @throws RequestException
     */
    public function vulnerabilityAudit(AuditService $service): void
    {
        $cveData = $service->getSeverities();
        $severityCount = count($cveData);
        $blocks = new Blocks($this->command);

        $this->command->message("");
        $blocks->addHeadline("Unitary Security Audit");

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
                $this->command->getAnsi()->style(['bold', 'brightRed'], "$severityCount vulnerabilities detected")
            );

            $this->command->message(
                $this->command->getAnsi()->italic("Update affected packages to resolve the issues.")
            );

        } else {
            $blocks->addText("All dependencies appear secure.", "green");
        }
        $this->command->message("");
    }

    /**
     * List all dependencies that you are using through composer
     *
     * @param AuditService $service
     * @return void
     */
    public function supplyChain(AuditService $service): void
    {
        $data = $service->dependencyCheck();
        $total = count($data);

        $blocks = new Blocks($this->command);
        $blocks->addSpace();
        $blocks->addHeadline("Unitary Dependency Audit");
        $blocks->addSection("Installed packages", function (Blocks $inst) use ($data) {
            foreach ($data as $row) {
                $inst = $inst->addOption($row['package'], "v{$row['version']}");
            }
            return $inst;
        });

        $blocks->addSpace();
        $blocks->addText($this->command->getAnsi()->style(["bold"], "Total packages: ") . $total);
        $blocks->addSpace();
    }

}
