<?php

namespace MaplePHP\Unitary\Interfaces;

use Psr\Http\Message\StreamInterface;
use MaplePHP\Unitary\TestCase;

interface BodyInterface
{
    /**
     * Show hidden messages
     *
     * @param bool $verbose
     * @return void
     */
    public function setVerbose(bool $verbose): void;

    /**
     * Show file paths even on passed tests
     *
     * @param bool $alwaysShowFiles
     * @return void
     */
    public function setAlwaysShowFiles(bool $alwaysShowFiles): void;

    /**
     * Pass the test case
     *
     * @param TestCase $testCase
     * @return void
     */
    public function setCase(TestCase $testCase): void;

    /**
     * Will expect the name of the test suite, this could
     * be the test file name
     *
     * @param string $title
     * @return void
     */
    public function setSuitName(string $title): void;

    /**
     * Expected to set a unique test checksum for testsuite
     *
     * @param string $checksum
     * @return void
     */
    public function setChecksum(string $checksum): void;


    /**
     * Should contain an array with tests
     *
     * @param array $tests
     * @return void
     */
    public function setTests(array $tests): void;

    /**
     * If true it means it should show test the user wants to show
     *
     * @param bool $show
     * @return void
     */
    public function setShow(bool $show): void;

    /**
     * Will expect to catch an unauthorized output outside the main stream
     *
     * @param string $addToOutput
     * @return string
     */
    public function outputBuffer(string $addToOutput = ''): string;

    /**
     * Expect to build your handler output
     *
     * @return void
     */
    public function buildBody(): void;


    /**
     * Expect to build your handler note output
     *
     * @return void
     */
    public function buildNotes(): void;


    /**
     * Must return a valid PSR Stream
     *
     * IMPORTANT: For everything to work correctly it should return a active
     * instance of a PSR stream, instead of returning a new instance.
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface;
}
