<?php

namespace MaplePHP\Unitary\Console\Services;

use Composer\Semver\Semver;
use FilesystemIterator;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\DTO\Traverse;
use MaplePHP\Http\Client;
use MaplePHP\Http\Exceptions\NetworkException;
use MaplePHP\Http\Exceptions\RequestException;
use MaplePHP\Http\Request;
use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;
use MaplePHP\Prompts\Themes\Blocks;
use MaplePHP\Unitary\Console\Enum\Severity;
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
        if (!is_file($this->path . '/composer.lock')) {
            throw new RuntimeException("Could not locate composer.lock file, try run composer install/update.");
        }

        $stream = new Stream($this->path . '/composer.lock', 'r');
        $lock = json_decode($stream->getContents(), true);

        return array_map(fn($p) => [
            'package' => $p['name'],
            'version' => ltrim($p['version'], 'v'),
        ], array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []));

    }

    public function getSeverities(): array
    {

        $packages = $this->dependencyCheck();

        $packages[] = [
            'package' => 'symfony/http-foundation',
            'version' => '5.4.22',
        ];

        $packages[] = [
            'package' => 'guzzlehttp/guzzle',
            'version' => '6.5.7',
        ];

        $advisories = $this->cveLookUpRequest($packages);


        if ($advisories !== []) {
            $hits = $this->getHits($advisories, $packages);
            usort($hits, function ($a, $b) {
                return $a['severityIndex'] <=> $b['severityIndex'];
            });
            return $hits;
        }
        return [];
    }


    /**
     * CVE Look up API request to packagist CVE API service
     *
     * @param array $packages
     * @return array
     * @throws NetworkException
     * @throws RequestException
     */
    protected function cveLookUpRequest(array $packages): array
    {
        $names = array_column($packages, 'package');
        $qs = http_build_query(['packages' => $names], '', '&', PHP_QUERY_RFC3986);
        $url = 'https://packagist.org/api/security-advisories/?' . $qs;

        $client = new Client();
        $request = new Request("GET", $url, [
            'Accept' => 'application/json',
        ]);
        $response = $client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('CSV database seem to be temporarily unavailable');
        }
        $data = json_decode($response->getBody()->getContents(), true);
        return array_filter($data['advisories'] ?? []);
    }

    /**
     * Filter out the packagist hit from affected versions
     *
     * @param array $advisories
     * @param array $packages
     * @return array
     */
    protected function getHits(array $advisories, array $packages): array
    {
        $hits = [];
        $objFind = Traverse::value($packages);
        foreach ($advisories as $pack => $advisory) {
            $foundPackage = $objFind->searchWithKey('package', $pack)->toArray();
            if ($foundPackage !== []) {
                foreach ($advisory as $adv) {
                    $affected = $adv['affectedVersions'];
                    $ranges = explode('|', $affected);
                    foreach ($ranges as $constraint) {
                        if (Semver::satisfies($foundPackage['version'], trim($constraint))) {
                            $severityObj = Severity::tryFrom($adv['severity']);
                            $severityIndex = ($severityObj?->index() ?? 0);
                            $hits[] = [
                                'package' => $pack,
                                'version' => $foundPackage['version'],
                                'id' => $adv['cve'] ?? ($adv['link'] ?? $adv['title']),
                                'title' => $adv['title'] ?? '',
                                'link' => $adv['link'] ?? '',
                                'severity' => $severityObj ?? 'Unknown',
                                'severityIndex' => $severityIndex,
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
