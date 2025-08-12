<?php

namespace MaplePHP\Unitary\Kernel\Services;

use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\Utils\FileIterator;
use RuntimeException;

class RunTestService extends AbstractTestService
{
    public function run(BodyInterface $handler): ResponseInterface
    {

        // /** @var DispatchConfig $config */
        // $config = $this->container->get("dispatchConfig");
        //$this->configs->isSmartSearch();

        $iterator = new FileIterator($handler, $this->args);
        $iterator = $this->iterateTest($iterator);

        // CLI Response
        if(PHP_SAPI === 'cli') {
            return $this->response->withStatus($iterator->getExitCode());
        }
        // Text/Browser Response
        return $this->response;
    }

    /**
     * @param FileIterator $iterator
     * @return FileIterator
     * @throws BlunderSoftException
     * @throws \MaplePHP\Container\Interfaces\ContainerExceptionInterface
     * @throws \MaplePHP\Container\Interfaces\NotFoundExceptionInterface
     */
    private function iterateTest(FileIterator $iterator): FileIterator
    {
        $defaultPath = $this->container->get("request")->getUri()->getDir();
        $defaultPath = ($this->configs->getProps()->path !== null) ? $this->configs->getProps()->path : $defaultPath;
        $path = ($this->args['path'] ?? $defaultPath);

        if(!isset($path)) {
            throw new RuntimeException("Path not specified: --path=path/to/dir");
        }
        $testDir = realpath($path);
        if(!file_exists($testDir)) {
            throw new RuntimeException("Test directory '$testDir' does not exist");
        }
        $iterator->enableExitScript(false);
        $iterator->executeAll($testDir, $defaultPath);
        return $iterator;
    }
}