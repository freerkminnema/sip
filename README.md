# sip-client
A SIP client in PHP.

```
composer require freerkminnema/sip-client
```

```php
$client = new \FreerkMinnema\Sip\Client([
    'host' => 'pbx.example.com',
    'username' => 'user',
    'password' => 'pass',
]);
$client->call('extension@pbx.example.com');
```
