<?php

namespace FreerkMinnema\Sip;

class Account
{
    public string $host;

    public string $username;

    public string $password;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
    }
}
