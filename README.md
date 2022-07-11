## How it working

### Requirements

- mysql/mariadb database
- redis cache driver

`php artisan monitor:db-replication` - check behind read server from master and ban it.

Ban server if:
- server not available
- param `Seconds_Behind_Master` more config params `behind_master` 

Lag servers will not run a read request.

Need to add multiple `read` servers in `config/database.php`
```
'mysql' => [
    ...
    'read' => [
        [
            'host'     => env('DBR1_HOST', '127.0.0.1'),
            'username' => env('DBR1_USERNAME', ''),
            'password' => env('DBR1_PASSWORD', ''),
        ],
        [
            'host'     => env('DBR2_HOST', '127.0.0.1'),
            'username' => env('DBR2_USERNAME', ''),
            'password' => env('DBR2_PASSWORD', ''),
        ],
        ...
    ],
]
```
**Important** param `host` is required!

### Installation

```
composer require ggmanuilov/laravel-slave-db-monitor
```

1. Registry service provider in `config/app.php`
```php
...
'providers' => [
    ...
    \Ggmanuilov\LaravelSlaveMonitor\Providers\DbReadMonitorServiceProvider::class,
],
...
```

2. Publish config `database-slave-monitor.php` (optionally):

```
php artisan vendor:publish --provider="Ggmanuilov\LaravelSlaveMonitor\Providers\DbReadMonitorServiceProvider"
```

3. Add command to `cron` command `* * * * * php artisan monitor:db-replication > /dev/null 2>&1`
