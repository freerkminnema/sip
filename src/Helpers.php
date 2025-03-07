<?php

namespace FreerkMinnema\Sip;

function determine_local_ip(): string
{
    // Try to connect to a public server to determine local IP
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($socket, '8.8.8.8', 53);
    socket_getsockname($socket, $localIP, $port);
    socket_close($socket);

    // Fallback if the above method doesn't work
    if (empty($localIP) || $localIP == '0.0.0.0') {
        // Try to get from server variables
        if (isset($_SERVER['SERVER_ADDR'])) {
            $localIP = $_SERVER['SERVER_ADDR'];
        } elseif (function_exists('gethostname') && function_exists('gethostbyname')) {
            $localIP = gethostbyname(gethostname());
        } else {
            $localIP = '127.0.0.1'; // Last resort fallback
        }
    }

    return $localIP;
}

function echo_if_cli(string $string): void
{
    if (php_sapi_name() === 'cli') {
        echo $string;
        echo "\n";
    }
}
