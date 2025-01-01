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
        try {
            try {
                $v = v::input($request->get(), [
                    'view' => v::nullable(v::stringVal())->setDefault('list')->setName('view'),
                    'page' => v::nullable(v::numericVal())->setDefault(1)->setName('page'),
                    'limit' => v::nullable(v::numericVal())->setName('limit'),
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if ($v['view'] == 'list') {
                $db = Server::select(['id', 'name', 'ip', 'os', 'location', 'cpu', 'memory', 'disk', 'uptime', 'status']);
            } else {
                $db = Server::select(['id', 'name', 'os', 'location', 'cpu', 'memory', 'disk', 'status']);
            }

            $page = $db->paginate($v['limit'], '*', 'page', $v['page']);
            $data['data'] = $page->items();
            $data['current_page'] = $page->currentPage(); // 当前页码
            $data['total_page'] = $page->lastPage(); // 总页数
            $data['total'] = $page->total(); // 总记录数
            $data['limit'] = $page->perPage(); // 每页记录数
            return success('success', $data);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
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

            $keyPrefix = 'bwm-node-';
            $node = ['node' => $v['ip'], 'monitor' => lockFile('host')];

            $publicKeyContent = lockFile('public_key');
            $publicKey = openssl_get_publickey($publicKeyContent);
            if (!$publicKey) {
                return badRequest('公钥无效');
            }

            $encryptedNode = '';
            if (!openssl_public_encrypt(json_encode($node), $encryptedNode, $publicKey)) {
                return badRequest('加密失败');
            }

            $key = $keyPrefix . base64_encode($encryptedNode);
            if (empty($key)) {
                return badRequest('密钥生成失败');
            }

            $v['key'] = $key;
            $v['status'] = 1;
            $v['uptime'] = 0;
            $data = Server::create($v);

            return success('添加成功', $data);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function delete(Request $request, $id): \support\Response
    {
        $server = Server::find($id);
        if (!$server) {
            return notFound('服务器不存在');
        }
        $server->delete();
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
