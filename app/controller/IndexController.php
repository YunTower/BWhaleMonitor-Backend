<?php

namespace app\controller;

use app\model\Config;
use app\model\Server;
use Carbon\Traits\Date;
use Exception;
use support\Db;
use support\Log;
use support\Request;

class IndexController
{
    protected $noNeedLogin = ['index'];

    public function index(Request $request): \support\Response
    {
        return json(["code" => 0, "message" => "BWhaleMonitor Backend is ok!"]);
    }

    public function info(Request $request): \support\Response
    {
        try {
            $data = [
                'title' => Config::find('title')->first()->value,
                'php' => PHP_VERSION,
                'os' => php_uname(),
                'http_api' => 'http://' . config('app.http_host') . ':' . config('app.http_point'),
                'websocket_api' => 'ws://' . config('app.websocket_host') . ':' . config('app.websocket_point'),
                'install_time' => date('Y-m-d H:i:s', lockFile('time')),
                'version' => config('app.version') . '-' . config('app.type')
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
        return success('success', $data);
    }
}
