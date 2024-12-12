<?php

use support\Db;
use support\Log;
use support\Response;

//json
function response_json($status, $data = null, $code = 0, $msg = 'success'): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]));
}

// 404
function notFound($msg = 'Not Found 资源不存在'): Response
{
    return new Response(404, ['Content-Type' => 'application/json'], json_encode(['code' => 404, 'msg' => $msg]));
}

// 500
function serverError($msg = 'Server Error 服务器错误'): Response
{
    return new Response(500, ['Content-Type' => 'application/json'], json_encode(['code' => 500, 'msg' => $msg]));
}

// 403
function forbidden($msg = 'Forbidden 禁止访问'): Response
{
    return new Response(403, ['Content-Type' => 'application/json'], json_encode(['code' => 403, 'msg' => $msg]));
}

// 401
function unauthorized($msg = 'Unauthorized 未授权'): Response
{
    return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'msg' => $msg]));
}

// 400
function badRequest($msg = 'Bad Request 请求错误'): Response
{
    return new Response(400, ['Content-Type' => 'application/json'], json_encode(['code' => 400, 'msg' => $msg]));
}

// success
function success($msg = 'success', $data = null, $header = []): Response
{
    return new Response(200, ['Content-Type' => 'application/json'] + $header, json_encode(['code' => 0, 'msg' => $msg, 'data' => $data]));
}

/**
 * 检测安装状态
 */
function check_install(): bool
{
    return file_exists(base_path('install.lock.json'));
}

/**
 * 检测数据表是否存在
 *
 * @param string $mode 模式：'all' - 检查所有表是否存在，'any' - 检查至少有一张表存在
 * @param bool $returnMissingTables 是否返回缺失的表名数组
 * @return bool|array
 */
function check_tables_existence(string $mode = 'all', bool $returnMissingTables = false): bool|array
{
    $tables = ['yt_monitor_server', 'yt_monitor_log', 'yt_monitor_config'];
    $missingTables = [];

    foreach ($tables as $table) {
        if (!Db::schema()->hasTable($table)) {
            $missingTables[] = $table;
        }
    }

    if ($mode === 'all') {
        if (empty($missingTables)) {
            return true;
        } else {
            return $returnMissingTables ? $missingTables : false;
        }
    } elseif ($mode === 'any') {
        return count($missingTables) < count($tables);
    }

    return false;
}

/**
 * 删除数据表
 *
 * @param array|string|null $tables 要删除的数据表名，为空或 "all" 则删除所有表
 * @return bool
 * @throws Exception
 */
function drop_tables(array|string $tables = null): bool
{
    // 数据表名白名单
    $whitelist = ['yt_monitor_server', 'yt_monitor_log', 'yt_monitor_config'];

    // 处理传入参数
    if ($tables === null || $tables === 'all') {
        $tables = $whitelist;
    } elseif (is_string($tables)) {
        $tables = [$tables];
    } elseif (!is_array($tables)) {
        throw new Exception("无效的参数类型: " . gettype($tables));
    }

    // 检测传入的表名是否在白名单中
    foreach ($tables as $table) {
        if (!in_array($table, $whitelist)) {
            throw new Exception("尝试删除未授权的数据表: $table");
        }
    }

    foreach ($tables as $table) {
        try {
            Db::schema()->dropIfExists($table);
        } catch (Exception $e) {
            throw new Exception('删除数据表失败：' . $e->getMessage());
        }
    }

    return true;
}

/**
 * 读取锁定文件的配置内容
 *
 * @param $name
 * @return bool|string
 * @throws Exception
 */
function lockFile($name): bool|string
{
    $lock_file = base_path('install.lock.json');
    if (!is_file($lock_file)) throw new Exception('文件【' . $lock_file . '】不存在');
    $lock_data = json_decode(file_get_contents($lock_file), true);
    return $lock_data[$name] ?? false;
}