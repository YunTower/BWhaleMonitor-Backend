<?php

namespace app\controller;

use app\model\Config;
use app\model\Server;
use Carbon\Traits\Date;
use support\Db;
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
        $data = [
            'title' => Config::find('title')->first()->value,
            'php' => PHP_VERSION,
            'os' => php_uname(),
            'http_api' => '',
            'websocket_api' => '',
            'install_time' => date('Y-m-d H:i:s', lockFile('time'))
        ];
        return success('success', $data);
    }
}
