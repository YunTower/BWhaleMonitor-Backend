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

            if (!$hostname = gethostname()) {
                return badRequest('获取服务器名称失败');
            }

            if ($host = gethostbyname($hostname) == $hostname) {
                return badRequest('获取服务器IP地址失败');
            }

            // 创建一段28位的密钥，前几位是bwm-node，其他几位由ip和服务器名称组成，要求包含大小写字母、数字和特殊符号
            $key = 'bwm-node-' . substr(md5($v['ip'] . $v['name']), 0, 28);

            var_dump($key);
            $data = Server::create($v + [
                    'key' => $key,
                    'status' => 1,
                    'uptime' => 0
                ]);

            return success('添加成功', $data);
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
