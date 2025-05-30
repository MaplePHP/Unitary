<?php

namespace TestLib;

class UserService {
    public function __construct(private Mailer $mailer) {}

    public function registerUser(string $email): bool {
        // register user logic...

        $this->mailer->addFromEmail($email);
        $this->mailer->addBCC("jane.doe@hotmail.com", "Jane Doe");
        $this->mailer->addBCC("lane.doe@hotmail.com", "Lane Doe");

        if(!filter_var($this->mailer->getFromEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email: " . $this->mailer->getFromEmail());
        }
        return true;
    }
}