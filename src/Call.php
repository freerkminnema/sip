<?php

namespace FreerkMinnema\Sip;

class Call
{
    public readonly string $id;

    public readonly string $branch;

    public readonly string $tag;

    public function __construct(string $ip)
    {
        $this->id = md5(uniqid())."@{$ip}";
        $this->branch = 'z9hG4bK'.md5(uniqid());
        $this->tag = md5(uniqid());
    }
}
