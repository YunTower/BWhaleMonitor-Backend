<?php

namespace plugin\webman\gateway;

use app\model\Config;
use app\model\Server;
use Illuminate\Support\Facades\Gate;
use Monolog\Logger;
use support\Log;
use GatewayWorker\Lib\Gateway;
use Workerman\Timer;
use Respect\Validation\Validator as v;

class Events
{
    private static Logger $log;

    public static function onWorkerStart(): void
    {
        self::$log = Log::channel('websocket');
        self::$log->info("WebSocket服务启动成功");
    }

    public static function onConnect($client_id): void
    {
        self::$log->info("被控/用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]已连接，等待认证...");
        // 加入未认证分组
        Gateway::joinGroup($client_id, 'unauthenticated');
        // 发送认证请求
        Gateway::sendToClient($client_id, json_encode(['type' => 'auth']));
        // 设置一个30秒的定时器，超时强制断连
        $_SESSION['auth_timer'] = Timer::add(30, function ($client_id) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'timeout', 'message' => '认证超时']));
            Gateway::closeClient($client_id);
            self::$log->info("被控/用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证超时，已断开连接");
        }, array($client_id), false);
    }

    public static function onMessage($client_id, $message): void
    {
        self::$log->info($message);
        $message = json_decode($message, true);
        switch ($message['type']) {
            case 'hello':
                if ($_SESSION && isset($_SESSION['heartbeat_timer'])) {
                    Timer::del($_SESSION['heartbeat_timer']);
                }
                $_SESSION['heartbeat_timer'] = Timer::add(30, function ($client_id) {
                    Gateway::closeClient($client_id);
                    self::$log->info("被控/用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]心跳超时，已断开连接");
                }, array($client_id), false);
                break;

            // 被控/用户认证
            case 'auth':
                // 认证对象是服务器
                if (isset($message['data']['type'])) {
                    if ($message['data']['type'] === 'server') {
                        if (!isset($message['data']['key'])) {
                            Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'error', 'message' => '认证失败，缺少参数']));
                            Gateway::closeClient($client_id);
                            return;
                        }
                        $server = Server::where('key', $message['data']['key'])->first();
                        if (!$server) {
                            self::$log->error("被控[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证失败，无效的Key");
                            Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'error', 'message' => '认证失败，无效的Key']));
                            Gateway::closeClient($client_id);
                            return;
                        }

                        // 检查IP地址是否一致
                        if ($server->ip !== $_SERVER['REMOTE_ADDR']) {
                            self::$log->error("被控[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证失败，IP地址不一致");
                            Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'error', 'message' => '认证失败，IP地址不一致']));
                            Gateway::closeClient($client_id);
                            return;
                        }

                        // 认证成功
                        ($_SESSION && $_SESSION['auth_timer']) && Timer::del($_SESSION['auth_timer']);
                        Gateway::leaveGroup($client_id, 'unauthenticated');
                        Gateway::joinGroup($client_id, 'onlineServer');
                        Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'success', 'message' => '认证成功']));
                        self::$log->info("被控[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证成功");
                        // 同步服务器信息
                        Gateway::sendToClient($client_id, json_encode(['type' => 'info']));

                        // 认证对象是用户
                    } else if ($message['data']['type'] === 'user') {
                        $v = v::input($message['data'], [
                            'type' => v::notEmpty()->setName('type'),
                            'role' => v::notEmpty()->setName('role'),
                            'username' => v::notEmpty()->setName('username')
                        ]);

                        if ($v['role'] === 'admin') {
                            if (Config::find('username')->value !== $v['username']) {
                                Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'error', 'message' => '非法的连接']));
                                Gateway::closeClient($client_id);
                                return;
                            }
                        }

                        ($_SESSION && $_SESSION['auth_timer']) && Timer::del($_SESSION['auth_timer']);
                        Gateway::leaveGroup($client_id, 'unauthenticated');
                        Gateway::joinGroup($client_id, 'user');
                        Gateway::sendToClient($client_id, json_encode(['type' => 'auth', 'status' => 'success', 'message' => '认证成功']));
                        self::$log->info("用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]认证成功");
                    } else {
                        Gateway::closeClient($client_id);
                        return;
                    }
                } else {
                    Gateway::closeClient($client_id);
                    return;
                }

                // 触发被控的心跳机制
                $_SESSION['heartbeat_timer'] = Timer::add(30, function ($client_id) {
                    Gateway::closeClient($client_id);
                    self::$log->info("被控/用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]心跳超时，已断开连接");
                }, array($client_id), false);
                break;

            // 被控基础信息上报
            case 'info':
                $memory = (int)$message['data']['memory']['total'];
                $cpu_list = array_map(function ($item) {
                    return [
                        "id" => $item['cpu'],
                        "vendorId" => $item['vendorId'],
                        "physicalId" => $item['physicalId'],
                        "cores" => $item['cores'],
                        "name" => $item['modelName'],
                        "mhz" => $item['mhz']
                    ];
                }, $message['data']['cpu']);
                $disk_list = array_map(function ($item) {
                    return [
                        "path" => $item['path'],
                        "total" => $item['total'],
                        "free" => $item['free'],
                        "used" => $item['used']
                    ];
                }, $message['data']['disk']);

                $server = Server::where('ip', $_SERVER['REMOTE_ADDR'])->first();
                if (!$server) {
                    Gateway::sendToClient($client_id, json_encode(['type' => $message['type'], 'status' => 'error', 'message' => '未知的被控']));
                    Gateway::closeClient($client_id);
                    return;
                }

                $update = [
                    'status' => 1,
                    'disk' => json_encode($disk_list),
                    'memory' => $memory,
                    'cpu' => json_encode($cpu_list)
                ];
                if ($server->os == 'auto') {
                    $update['os'] = ucwords($message['data']['os']);
                }
                $server->update($update);
                Gateway::sendToClient($client_id, json_encode(['type' => $message['type'], 'status' => 'success', 'message' => '被控信息更新成功']));
                break;

            // 客户端发送监听的服务器
            // TODO 前端发送的要监听的服务器，根据不同的client_id存在session中
            case 'listen':
                break;

            // 被控IO上报
            // TODO 实现被控端IO信息入库功能，当查询到某个用户正在监听此服务器的IO时，转发给该用户
            case 'io':
                break;
            default:
                Gateway::sendToClient($client_id, json_encode(['type' => $message['type'], 'status' => 'error', 'message' => '未知消息类型']));
        }
    }

    public static function onClose($client_id): void
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

        if ($_SESSION && $_SESSION['auth_timer']) {
            Timer::del($_SESSION['auth_timer']);
        }
        if ($_SESSION && isset($_SESSION['heartbeat_timer'])) {
            Timer::del($_SESSION['heartbeat_timer']);
        }
        self::$log->info("被控/用户[{$_SERVER['REMOTE_ADDR']} ({$client_id})]已断开连接");
    }
}
