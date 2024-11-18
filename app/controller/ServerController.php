<?php

namespace app\controller;

use support\Request;

class ServerController
{
    public function get(Request $request): \support\Response
    {
        static $data;
        if (!$data) {
            $data = file_get_contents(public_path("data/list.json"));
        }
        return json($data);
    }

    public function search(Request $request): \support\Response
    {
    }

    public function add(Request $request): \support\Response
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

    public function delete(Request $request): \support\Response
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
