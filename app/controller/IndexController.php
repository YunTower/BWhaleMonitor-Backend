<?php

namespace app\controller;

use support\Db;
use support\Request;

class IndexController
{
    public function index(Request $request): \support\Response
    {
        return json(["code" => 0, "message" => "BWhaleMonitor Backend is ok!"]);
    }
}
