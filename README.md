# A SIP client in PHP.

```
composer require freerkminnema/sip
```

```php
$client = new \FreerkMinnema\Sip\Client([
    'host' => 'pbx.example.com',
    'username' => 'user',
    'password' => 'pass',
]);
$client->call('extension@pbx.example.com');
```

Once it starts the ringing, it won't cancel. You'll need to pick up the phone or wait for the time-out.
