<?php

namespace FreerkMinnema\Sip;

class Message
{
    private Method $method;

    private ?Authentication $authentication = null;

    private int $session;

    private Account $from;

    private string $to;

    private string $ip;

    private Call $call;

    private int $cseq;

    private array $routes = [];
    private ?string $tag = null;

    public function setMethod(Method $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function setSession(int $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function setFrom(Account $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function setTo(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function setCall(Call $call): self
    {
        $this->call = $call;

        return $this;
    }

    public function setAuthentication(Authentication $authentication): self
    {
        $this->authentication = $authentication;

        return $this;
    }

    public function setCseq(int $cseq): self
    {
        $this->cseq = $cseq;

        return $this;
    }

    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    public function setTag(?string $tag): self
    {
        if (! empty($tag)) {
            $this->tag = ";tag={$tag}";
        }

        return $this;
    }

    public function __toString(): string
    {
        $lines = [
            "{$this->method->value} sip:{$this->to} SIP/2.0",
            "Via: SIP/2.0/UDP {$this->ip}:5060;branch={$this->call->branch}",
            "From: <sip:{$this->from->username}@{$this->from->host}>;tag={$this->call->tag}",
            "To: <sip:{$this->to}>{$this->tag}",
            "Call-ID: {$this->call->id}",
            "CSeq: {$this->cseq} {$this->method->value}",
            'Max-Forwards: 70',
            'User-Agent: freerkminnema/sip-client',
            // Date: Fri, 07 Mar 2025 12:00:00 GMT. todo
        ];

        if (! empty($this->routes)) {
            foreach ($this->routes as $route) {
                $lines[] = "Route: {$route}";
            }
        }

        if ($this->authentication) {
            $lines[] = $this->authentication->getHeader($this->from, $this->to, $this->method);
        }

        if ($this->method === Method::INVITE) {
            $lines[] = "Contact: <sip:{$this->from->username}@{$this->ip}:5060>";
            $lines[] = 'Allow: INVITE, ACK, CANCEL, BYE, NOTIFY, REFER, OPTIONS, INFO, SUBSCRIBE';
            $lines[] = 'Supported: replaces, timer';
            $lines[] = 'Content-Type: application/sdp';

            $rtpPort = 49170;
            $sdp = [
                'v=0',
                "o={$this->from->username} {$this->session} {$this->session} IN IP4 {$this->ip}",
                's=SIP Call',
                "c=IN IP4 {$this->ip}",
                't=0 0',
                // Media description
                "m=audio {$rtpPort} RTP/AVP 0 8 101", // audio media, port, transport, payload types (0=PCMU/G.711, 8=PCMA, 101=telephone-event)"
                'a=rtpmap:0 PCMU/8000', // PCMU/G.711 codec
                'a=rtpmap:8 PCMA/8000', // PCMA codec
                'a=rtpmap:101 telephone-event/8000', // RFC2833 DTMF
                'a=fmtp:101 0-16', // DTMF events
                'a=ptime:20', // packet time 20ms
                'a=sendrecv', // media direction
            ];
            $sdp = implode("\r\n", $sdp)."\r\n";

            $lines[] = 'Content-Length: '.strlen($sdp)."\r\n";
            $lines[] = $sdp;
        } else {
            $lines[] = "Content-Length: 0\r\n";
        }

        return implode("\r\n", $lines);
    }
}
