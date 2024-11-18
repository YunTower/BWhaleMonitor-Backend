<?php

namespace app\controller;

use support\Db;
use support\Request;

class IndexController
{
    public function index(Request $request)
    {
      Db::insert(['name'=>'test']);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
