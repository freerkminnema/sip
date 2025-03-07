<?php

namespace FreerkMinnema\Sip;

class Client
{
    protected Account $account;

    protected Session $session;

    public function __construct(array $config)
    {
        $this->account = new Account($config);
        $this->session = new Session($this->account);
    }

    public function register()
    {
        // todo
    }

    public function call(string $to): void
    {
        $this->session->initiateCall($to);
    }
}
