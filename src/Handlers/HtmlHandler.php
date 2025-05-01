<?php

declare(strict_types=1);

namespace MaplePHP\Unitary\Handlers;

use MaplePHP\Http\Stream;
use MaplePHP\Prompts\Command;

final class HtmlHandler implements HandlerInterface
{
    private Stream $stream;
    private Command $command;

    /**
     * Construct the file handler
     * The handler will pass stream to a file
     */
    public function __construct()
    {
        $this->stream = new Stream(Stream::TEMP);
        $this->command = new Command($this->stream);
        $this->command->getAnsi()->disableAnsi(true);
    }

    /**
     * Access the command stream
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * Execute the handler
     * This will automatically be called inside the Unit execution
     * @return void
     */
    public function execute(): void
    {
        $this->stream->rewind();
        $out = $this->stream->getContents();
        $style = 'background-color: #F1F1F1; color: #000; font-size: 2rem; font-weight: normal; font-family: "Lucida Console", Monaco, monospace;';
        $out = str_replace(["[", "]"], ['<span style="background-color: #666; color: #FFF; padding: 4px 2px">', '</span>'], $out);
        echo '<pre style="' . $style . '">' . $out . '</pre>';
    }
}
