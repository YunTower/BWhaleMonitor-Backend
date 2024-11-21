<?php

namespace app\controller;

use support\Db;
use support\Request;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
/
class SettingController
{
    public function install(Request $request): \support\Response
    {
        try {
            try {
                // 验证输入数据
                $v = v::input($request->post(), [
                    'title' => v::nullable(v::length(0, 50)->setDefault('蓝鲸服务器探针'))->setName('title'),
                    'username'=>v::notEmpty()->length(0,50)->setName('username'),
                    'password'=>v::notEmpty()->length(6,50)->setName('password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }
        } catch (\Exception $e) {
            return serverError();
        }
    }
}
