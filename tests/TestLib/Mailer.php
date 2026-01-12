<?php

namespace TestLib;

class Mailer
{
    public $from = "";
    public $bcc = "";

    public function __construct()
    {

    }


    public function send(): string
    {
        $this->sendEmail($this->getFromEmail());

        return $this->privateMethod();
    }

    public function sendEmail(string $email, string $name = "daniel"): string
    {
        if(!$this->isValidEmail($email)) {
            throw new \Exception("Invalid email");
        }
        return "Sent email";
    }

    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function setFromEmail(string $email): self
    {
        $this->from = $email;
        return $this;
    }

    public function getFromEmail(): string
    {
        return !empty($this->from) ? $this->from : "empty email";
    }

    private function privateMethod(): string
    {
        return "HEHEHE";
    }

    /**
     * Add from email address
     *
     * @param string $email
     * @return void
     */
    public function addFromEmail(string $email, string $name = ""): void
    {
        $this->from = $email;
    }

    /**
     * Add a BCC (blind carbon copy) email address
     *
     * @param string $email The email address to be added as BCC
     * @param string $name The name associated with the email address, default is "Daniel"
     * @param mixed $testRef A reference variable, default is "Daniel"
     * @return void
     */
    public function addBCC(string $email, string $name = "Daniel", &$testRef = "Daniel"): void
    {
        $this->bcc = $email;
    }

    public function test(...$params): void
    {
        $this->test2();
    }

    public function test2(): void
    {
        echo "Hello World\n";
    }

}