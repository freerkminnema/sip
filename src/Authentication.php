<?php

namespace FreerkMinnema\Sip;

class Authentication
{
    public readonly string $type;

    public readonly string $header;

    public function __construct(string $type, string $header)
    {
        $this->type = $type;
        $this->header = $header;
    }

    public function getHeader(Account $account, string $to, Method $method): string
    {
        // Parse authentication header
        $realm = '';
        $nonce = '';
        $qop = '';
        $opaque = '';

        if (preg_match('/realm="([^"]+)"/', $this->header, $matches)) {
            $realm = $matches[1];
        }

        if (preg_match('/nonce="([^"]+)"/', $this->header, $matches)) {
            $nonce = $matches[1];
        }

        if (preg_match('/qop="?([^",]+)"?/', $this->header, $matches)) {
            $qop = $matches[1];
        }

        if (preg_match('/opaque="([^"]+)"/', $this->header, $matches)) {
            $opaque = $matches[1];
        }

        // Create authentication response
        $uri = "sip:{$to}";
        $nc = '00000001';
        $cnonce = md5(uniqid());

        // Calculate digest response
        $ha1 = md5("{$account->username}:{$realm}:{$account->password}");
        $ha2 = md5("{$method->value}:{$uri}");

        $response_digest = '';
        if ($qop) {
            $response_digest = md5("$ha1:$nonce:$nc:$cnonce:$qop:$ha2");
        } else {
            $response_digest = md5("$ha1:$nonce:$ha2");
        }

        $name = ($this->type == 'www') ? 'Authorization' : 'Proxy-Authorization';
        $value = 'Digest username="'.$account->username.'", '.
            'realm="'.$realm.'", '.
            'nonce="'.$nonce.'", '.
            'uri="'.$uri.'", '.
            'response="'.$response_digest.'", '.
            'algorithm=MD5';

        if ($qop) {
            $value .= ', qop='.$qop.', nc='.$nc.', cnonce="'.$cnonce.'"';
        }

        if ($opaque) {
            $value .= ', opaque="'.$opaque.'"';
        }

        return "{$name}: {$value}";
    }
}
