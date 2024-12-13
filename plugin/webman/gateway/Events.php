<?php

namespace plugin\webman\gateway;

use support\Log;
use GatewayWorker\Lib\Gateway;

class Events
{
    private static $log;

    public static function onWorkerStart($worker): void
    {
        echo "【云塔服务器探针】WebSocket服务启动成功\n";
        self::$log = Log::channel('websocket');
        self::$log->info("【云塔服务器探针】WebSocket服务启动成功\n");
    }

    public static function onConnect($client_id): void
    {
        self::$log->info("【云塔服务器探针】节点[{$client_id}]已连接，等待认证...\n");
        // 加入未认证分组
        Gateway::joinGroup($client_id, 'unauthenticated');
        // 发送认证请求
        Gateway::sendToClient($client_id, json_encode(['type' => 'auth']));
    }

    public static function onWebSocketConnect($client_id, $data)
    {

    }

    public static function onMessage($client_id, $message): void
    {
        $message = json_decode($message, true);
        switch ($message['type']) {
            case 'auth':
                break;
            default:
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => '未知消息类型']));
        }
    }

    public static function onClose($client_id)
    {
        echo "【云塔服务器探针】节点[{$client_id}]已断开连接\n";
    }
}
