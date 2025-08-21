<?php

namespace MaplePHP\Unitary\Console\Services;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;
use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Unitary\Discovery\TestDiscovery;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Unit;
use RuntimeException;

class RunTestService extends AbstractMainService
{
    public function run(BodyInterface $handler): ResponseInterface
    {
        $iterator = new TestDiscovery();
        $iterator->enableVerbose($this->props->verbose);
        $iterator->enableFailFast($this->props->failFast);
        $iterator->enableSmartSearch($this->props->smartSearch);
        $iterator->addExcludePaths($this->props->exclude);
        $iterator->setDiscoverPattern($this->props->discoverPattern);

        $iterator = $this->iterateTest($iterator, $handler);

        // CLI Response
        if (PHP_SAPI === 'cli') {
            return $this->response->withStatus($iterator->getExitCode());
        }
        // Text/Browser Response
        return $this->response;
    }

    /**
     * @param TestDiscovery $iterator
     * @param BodyInterface $handler
     * @return TestDiscovery
     * @throws BlunderSoftException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function iterateTest(TestDiscovery $iterator, BodyInterface $handler): TestDiscovery
    {
        $defaultPath = $this->container->get("request")->getUri()->getDir();
        $defaultPath = ($this->configs->getProps()->path !== null) ? $this->configs->getProps()->path : $defaultPath;
        $path = ($this->args['path'] ?? $defaultPath);

        if (!isset($path)) {
            throw new RuntimeException("Path not specified: --path=path/to/dir");
        }
        $testDir = realpath($path);
        if (!file_exists($testDir)) {
            throw new RuntimeException("Test directory '$path' does not exist");
        }
        $iterator->executeAll($testDir, $defaultPath, function ($file) use ($handler) {
            $unit = new Unit($handler);
            $unit->setShowErrorsOnly($this->props->errorsOnly);
            $unit->setFailFast($this->props->failFast);
            $unit->setShow($this->props->show);
            $unit->setFile($file);
            $unit->setVerbose($this->props->verbose);
            $unit->setAlwaysShowFiles($this->props->alwaysShowFiles);
            return $unit;
        });
        return $iterator;
    }

}
