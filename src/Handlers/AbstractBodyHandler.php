<?php

namespace MaplePHP\Unitary\Handlers;

use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Unitary\Interfaces\BodyInterface;
use MaplePHP\Unitary\TestCase;

class AbstractBodyHandler implements BodyInterface
{
    protected TestCase $case;
    protected string $suitName = "";
    protected string $checksum = "";
    protected bool $show = false;
    protected array $tests;
    protected string $outputBuffer = "";

    /**
     * {@inheritDoc}
     */
    public function setCase(TestCase $testCase): void
    {
        $this->case = $testCase;
    }

    /**
     * {@inheritDoc}
     */
    public function setSuitName(string $title): void
    {
        $this->suitName = $title;
    }

    /**
     * {@inheritDoc}
     */
    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * {@inheritDoc}
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * {@inheritDoc}
     */
    public function setShow(bool $show): void
    {
        $this->show = $show;
    }

    /**
     * {@inheritDoc}
     */
    public function outputBuffer(string $addToOutput = ''): string
    {
        $out = (ob_get_level() > 0) ? ob_get_clean() : '';
        $this->outputBuffer = $out . $addToOutput;
        return $this->outputBuffer;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBody(): void
    {
        throw new \RuntimeException('Your handler is missing the execution method.');
    }

    /**
     * {@inheritDoc}
     */
    public function buildNotes(): void
    {

        throw new \RuntimeException('Your handler is missing the execution method.');
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        return new Stream();
    }

    /**
     * {@inheritDoc}
     */
    public function getCommand(): StreamInterface
    {
        return new Stream();
    }


    /**
     * Make a file path into a title
     * @param string $file
     * @param int $length
     * @param bool $removeSuffix
     * @return string
     */
    protected function formatFileTitle(string $file, int $length = 3, bool $removeSuffix = true): string
    {
        $file = explode("/", $file);
        if ($removeSuffix) {
            $pop = array_pop($file);
            $file[] = substr($pop, (int)strpos($pop, 'unitary') + 8);
        }
        $file = array_chunk(array_reverse($file), $length);
        $file = implode("\\", array_reverse($file[0]));
        //$exp = explode('.', $file);
        //$file = reset($exp);
        return ".." . $file;
    }

}