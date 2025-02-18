<?php

use Webman\GatewayWorker\Gateway;
use Webman\GatewayWorker\BusinessWorker;
use Webman\GatewayWorker\Register;

return [
    'gateway' => [
        'handler' => Gateway::class,
        'listen' => 'websocket://' . config('app.websocket_host') . ':' . config('app.websocket_point'),
        'count' => 2,
        'reloadable' => true,
        'constructor' => ['config' => [
            'lanIp' => '127.0.0.1',
            'startPort' => 2300,
            'registerAddress' => '127.0.0.1:1236',
            'onConnect' => function () {
            },
        ]]
    ],
    'worker' => [
        'handler' => BusinessWorker::class,
        'count' => cpu_count() * 2,
        'constructor' => ['config' => [
            'eventHandler' => plugin\webman\gateway\Events::class,
            'name' => 'ChatBusinessWorker',
            'registerAddress' => '127.0.0.1:1236',
        ]]
    ],
    'register' => [
        'handler' => Register::class,
        'listen' => 'text://127.0.0.1:1236',
        'count' => 1, // Must be 1
        'reloadable' => false,
        'constructor' => []
    ],
];
