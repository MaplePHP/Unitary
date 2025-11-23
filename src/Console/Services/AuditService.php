<?php

namespace MaplePHP\Unitary\Console\Services;

use Composer\Semver\Semver;
use FilesystemIterator;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\DTO\Traverse;
use MaplePHP\Http\Client;
use MaplePHP\Http\Request;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class AuditService
{

    const ENV = [];
    private Command $command;
    private string|false $path;


    public function __construct(ContainerInterface $container)
    {
        $this->command = $container->get("command");
        $this->path = realpath(__DIR__ . "/../../../");
    }

    /**
     * Get all dependencies from locked compoer file
     * @return array
     */
    public function dependencyCheck(): array
    {
        if(!is_file($this->path . '/composer.lock')) {
            throw new RuntimeException("Could not locate composer.lock file, try run composer install/update.");
        }

        $stream = new Stream($this->path . '/composer.lock', 'r');
        $lock = json_decode($stream->getContents(), true);

        return array_map(fn($p) => [
            'package' => $p['name'],
            'version' => ltrim($p['version'], 'v'),
        ], array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []));

    }

    // GET SERVERIES ONLY OF BOOL
    public function rrrr() {

        $packages = $this->dependencyCheck();


        $packages[] = [
            'package' => 'symfony/http-foundation',
            'version' => '5.4.22',
        ];

        $packages[] = [
            'package' => 'guzzlehttp/guzzle',
            'version' => '6.5.7',
        ];


        $advisories = $this->packagist($packages);
        if ($advisories !== false) {
            $hits = $this->getHits($advisories, $packages);

            $blocks = new Blocks($this->command);
            $blocks->addHeadline("Vulnerability has been found");





            $blocks->addTableSection(function(Blocks $inst) use ($hits) {
                foreach($hits as $row) {
                    $inst
                        ->addTable("Title", $row['title'])
                        ->addTable("Package", $row['package'])
                        ->addTable("Version", $row['version'])
                        ->addTable("Severity", $this->severity($row['severity']))
                        ->addTable("CVE ID", $row['id'])
                        ->addTable("Link", $row['link']);
                }
            });

        } else {
            $this->command->error('No serverity found');
        }


    }

    /**
     * Get severity in with a severity color
     *
     * @param string $severity
     * @return string
     */
    public function severity(string $severity): string
    {
        switch (strtolower($severity)) {
            case 'low':
                return $this->command->getAnsi()->blue('Low');
            case 'medium':
                return $this->command->getAnsi()->yellow('Medium');
        }

        return $this->command->getAnsi()->red('High');
    }

    /**
     * Packagist API request
     *
     * @param array $packages
     * @return array|false
     * @throws \MaplePHP\Http\Exceptions\NetworkException
     * @throws \MaplePHP\Http\Exceptions\RequestException
     */
    function packagist(array $packages)
    {
        $names = array_column($packages, 'package');
        $qs = http_build_query(['packages' => $names], '', '&', PHP_QUERY_RFC3986);

        $client = new Client();
        $request = new Request("GET", 'https://packagist.org/api/security-advisories/?' . $qs, [
            'Accept' => 'application/json',
        ]);
        $response = $client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('CSV database seem to be temporarily unavailable');
        }
        $data = json_decode($response->getBody()->getContents(), true);
        $advisories = array_filter($data['advisories'] ?? []);
        if($advisories !== []) {
            return $advisories;
        }
        return false;
    }

    /**
     * Filter out the packagist hit from affected versions
     *
     * @param array $advisories
     * @param array $packages
     * @return array
     */
    public function getHits(array $advisories, array $packages): array
    {
        $hits = [];
        $objFind = Traverse::value($packages);
        foreach($advisories as $pack => $advisory) {
            $foundPackage = $objFind->searchWithKey('package', $pack)->toArray();
            if($foundPackage !== []) {
                foreach ($advisory as $adv) {

                    $affected = $adv['affectedVersions'];
                    $ranges = explode('|', $affected);
                    foreach ($ranges as $constraint) {
                        if (Semver::satisfies($foundPackage['version'], trim($constraint))) {
                            $hits[] = [
                                'package' => $pack,
                                'version' => $foundPackage['version'],
                                'id'      => $adv['cve'] ?? ($adv['link'] ?? $adv['title']),
                                'title'   => $adv['title'] ?? '',
                                'link'    => $adv['link'] ?? '',
                                'severity' => $adv['severity'] ?? 'Unknown',
                                'reportedAt' => $adv['reportedAt'] ?? 'Unknown',
                            ];
                        }
                    }

                }
            }
        }
        return $hits;
    }

}
