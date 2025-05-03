<?php

declare(strict_types=1);

namespace MaplePHP\Unitary\Handlers;

use MaplePHP\Http\Stream;
use MaplePHP\Http\UploadedFile;
use MaplePHP\Prompts\Command;

final class FileHandler implements HandlerInterface
{
    private string $file;
    private Stream $stream;
    private Command $command;

    /**
     * Construct the file handler
     * The handler will pass stream to a file
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->stream = new Stream(Stream::TEMP);
        $this->command = new Command($this->stream);
        $this->command->getAnsi()->disableAnsi(true);
        $this->file = $file;
    }

    /**
     * Access the command stream
     *
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * Execute the handler
     * This will automatically be called inside the Unit execution
     *
     * @return void
     */
    public function execute(): void
    {
        $upload = new UploadedFile($this->stream);
        $upload->moveTo($this->file);
    }
}
