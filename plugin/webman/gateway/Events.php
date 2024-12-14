<?php

namespace plugin\webman\gateway;

use app\model\Server;
use support\Log;
use GatewayWorker\Lib\Gateway;
use Workerman\Timer;

class Events
{
    private static $log;

    public static function onWorkerStart($worker): void
    {
        self::$log = Log::channel('websocket');
        self::$log->info("WebSocket服务启动成功");
    }

    public static function onConnect($client_id): void
    {
        self::$log->info("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]已连接，等待认证...");
        // 加入未认证分组
        Gateway::joinGroup($client_id, 'unauthenticated');
        // 发送认证请求
        Gateway::sendToClient($client_id, json_encode(['type' => 'auth']));
        // 设置一个30秒的定时器，超时强制断连
        $_SESSION['auth_timer'] = Timer::add(30, function ($client_id) {
            Gateway::closeClient($client_id);
            self::$log->info("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证超时，已断开连接");
        }, array($client_id), false);
    }

    public static function onMessage($client_id, $message): void
    {
        $message = json_decode($message, true);
        switch ($message['type']) {
            case 'hi':
                // 清除心跳
                Timer::del($_SESSION['heartbeat_timer']);
                Gateway::sendToClient($client_id, json_encode(['type' => 'hello']));
                // 开启心跳
                $_SESSION['heartbeat_timer'] = Timer::add(30, function ($client_id) {
                    Gateway::closeClient($client_id);
                }, array($client_id), false);
                break;
            case 'auth':
                $server = Server::where('key', $message['data']['key'])->first();
                if (!$server) {
                    self::$log->error("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证失败，无效的Key");
                    Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => '认证失败，无效的Key']));
                    Gateway::closeClient($client_id);
                }

                // 检查IP地址是否一致
                if ($server->ip !== $_SERVER['REMOTE_ADDR']) {
                    self::$log->error("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证失败，IP地址不一致");
                    Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => '认证失败，IP地址不一致']));
                    Gateway::closeClient($client_id);
                }

                // 认证成功
                Timer::del($_SESSION['auth_timer']);
                Gateway::leaveGroup($client_id, 'unauthenticated');
                Gateway::joinGroup($client_id, 'onlineServer');
                Gateway::sendToClient($client_id, json_encode(['type' => 'hello']));
                self::$log->info("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证成功");
                break;
            default:
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => '未知消息类型']));
        }
    }

    public static function onClose($client_id)
    {
        //获取所有分组
        $groups = Gateway::getAllGroupIdList();
        foreach ($groups as $group) {
            // 获取分组下所有客户端
            $clients = Gateway::getClientSessionsByGroup($group);
            // 遍历客户端
            foreach ($clients as $key => $client) {
                // 找到客户端并退出分组
                if ($key === $client_id) {
                    Gateway::leaveGroup($client_id, $group);
                    break;
                }
            }
        }

        if ($_SESSION['auth_timer']) {
            Timer::del($_SESSION['auth_timer']);
        }
        if (isset($_SESSION['heartbeat_timer'])) {
            Timer::del($_SESSION['heartbeat_timer']);
        }
        self::$log->info("节点[{$_SERVER['REMOTE_ADDR']} ({$client_id})]已断开连接");
    }
}
