<?php

namespace app\controller;

use app\model\Server;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Log;
use support\Request;
use Exception;

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
        try {
            try {
                $v = v::input($request->post(), [
                    'name' => v::notEmpty()->setName('name'),
                    'ip' => v::notEmpty()->ip()->setName('ip'),
                    'os' => v::notEmpty()->setName('os'),
                    'location' => v::notEmpty()->setName('location')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            $check_ip = Server::where('ip', $v['ip'])->first();
            if ($check_ip) {
                return badRequest('已存在相同IP地址的服务器');
            }

            Server::create($v);
            return success();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function delete(Request $request): \support\Response
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
