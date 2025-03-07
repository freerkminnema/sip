<?php

namespace FreerkMinnema\Sip;

use Socket;

class Session
{
    private int $id;

    private Account $account;

    private ?Socket $socket = null;

    private string $ip;

    private int $cseq;

    private ?Authentication $authentication = null;

    private array $routes = [];

    public function __construct(Account $account)
    {
        $this->id = time();
        $this->account = $account;
        $this->ip = determine_local_ip();
        $this->cseq = 0;
    }

    public function initiateCall(string $to): void
    {
        $this->initSocket();

        $call = new Call($this->ip);

        $invite = (new Message)
            ->setMethod(Method::INVITE)
            ->setCseq(++$this->cseq)
            ->setSession($this->id)
            ->setFrom($this->account)
            ->setTo($to)
            ->setIp($this->ip)
            ->setCall($call)
            ->setRoutes($this->routes);

        $this->sendMessage($invite);
        $this->waitForMessage();

        $invite->setAuthentication($this->authentication);
        $invite->setCseq(++$this->cseq);
        $invite->setRoutes($this->routes);

        $this->sendMessage($invite);
        $this->waitForMessage();

        $cancel = (new Message)
            ->setMethod(Method::CANCEL)
            ->setCseq($this->cseq)
            ->setSession($this->id)
            ->setFrom($this->account)
            ->setTo($to)
            ->setIp($this->ip)
            ->setCall($call)
            ->setRoutes($this->routes);

        sleep(5);
        $this->sendMessage($cancel);
        $this->waitForMessage();
        socket_close($this->socket);
    }

    private function initSocket(): void
    {
        if ($this->socket) {
            return;
        }

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new \RuntimeException('Failed to create socket: '.socket_strerror(socket_last_error()));
        }
        // Set non-blocking mode for continuous reading
        socket_set_nonblock($socket);

        $this->socket = $socket;
    }

    private function processResponse(string $response): bool
    {
        if (preg_match_all('/Record-Route: (.+)/', $response, $matches)) {
            $this->routes = $matches[1];
        }

        if (strpos($response, 'SIP/2.0 401 Unauthorized') !== false) {
            if (! preg_match('/WWW-Authenticate: (.+)/', $response, $matches)) {
                throw new \RuntimeException('Failed to parse WWW-Authenticate header');
            }
            $this->authentication = new Authentication('www', $matches[1]);

            return true;
        } elseif (strpos($response, 'SIP/2.0 407 Proxy Authentication Require') !== false) {
            if (! preg_match('/Proxy-Authenticate: (.+)/', $response, $matches)) {
                throw new \RuntimeException('Failed to parse Proxy-Authenticate header');
            }
            $this->authentication = new Authentication('proxy', $matches[1]);

            return true;
        } elseif (strpos($response, 'SIP/2.0 180 Ringing') !== false ||
            strpos($response, 'SIP/2.0 183 Session Progress') !== false) {
            return true;
        }

        return false;
    }

    private function sendMessage(Message $message): void
    {
        echo_if_cli('>> Sending message >>');
        echo_if_cli($message);

        $result = socket_sendto($this->socket, $message, strlen($message), 0, $this->account->host, 5060);
        if ($result === false) {
            socket_close($this->socket);

            throw new \RuntimeException('Failed to send data: '.socket_strerror(socket_last_error($this->socket)));
        }
    }

    private function waitForMessage(int $timeout = 15): void
    {
        $startTime = time();
        while (time() - $startTime < $timeout) {
            $response = '';
            $from = '';
            $port = 0;
            // Try to receive response
            $received = socket_recvfrom($this->socket, $response, 4096, 0, $from, $port);

            if ($received !== false && $received > 0) {
                echo_if_cli('<< Received message <<');
                echo_if_cli($response);
                if ($this->processResponse($response)) {
                    return;
                }
            }

            // Small delay to prevent CPU overload
            usleep(100_000);
        }

        // throw new \RuntimeException('Timeout while waiting for response');
    }
}
