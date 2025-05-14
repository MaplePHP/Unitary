<?php

namespace MaplePHP\Unitary;

class TestConfig
{

    public ?string $message;
    public bool $skip = false;
    public string $select = "";

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Statically make instance.
     *
     * @param string $message
     * @return self
     */
    public static function make(string $message): self
    {
        return new self($message);
    }

    /**
     * Sets the select state for the current instance.
     *
     * @param string $key The key to set.
     * @return self
     */
    public function setSelect(string $key): self
    {
        $this->select = $key;
        return $this;
    }

    /**
     * Sets the message for the current instance.
     *
     * @param string $message The message to set.
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Sets the skip state for the current instance.
     *
     * @param bool $bool Optional. The value to set for the skip state. Defaults to true.
     * @return self
     */
    public function setSkip(bool $bool = true): self
    {
        $this->skip = $bool;
        return $this;
    }

}