<?php

namespace app\controller;

use app\model\Config;
use Exception;
use support\Db;
use support\Log;
use support\Request;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Response;

class SettingController
{
    public function install(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'title' => v::notEmpty()->length(0, 50)->setName('title'),
                    'username' => v::notEmpty()->length(0, 50)->setName('username'),
                    'password' => v::notEmpty()->length(8, 50)->setName('password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }
            $v['password'] = password_hash($v['password'], PASSWORD_DEFAULT);
            $data = $v + [
                    'interval' => 5,
                    'guest' => false,
                    'guest_password' => null
                ];
            foreach ($data as $key => $value) {
                Config::updateOrInsert(['name' => $key, 'value' => $value]);
            }

            return success('', Config::get());
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'trace' => $e->getTrace()]);
            return serverError();
        }
    }

    public function save(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'title' => v::nullable(v::length(0, 50)->setDefault('蓝鲸服务器探针'))->setName('title'),
                    'username' => v::notEmpty()->length(0, 50)->setName('username'),
                    'password' => v::notEmpty()->length(8, 50)->setName('password'),
                    'interval' => v::intVal()->between(1, 60)->setName('interval'),
                    'guest' => v::boolVal()->setName('guest'),
                    'guest_password' => v::nullable(v::length(8, 50))->setName('guest_password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            Config::updateOrInsert($v);
            return success();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'trace' => $e->getTrace()]);
            return serverError();
        }
    }

    public function get(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'columns' => v::notEmpty()->stringType()->setName('columns'),
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            $config = [];
            $columns = explode(',', $v['columns']);
            foreach ($columns as $column) {
                $config[$column] = Config::get($column);
            }
            return success('success', $config);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'trace' => $e->getTrace()]);
            return serverError();
        }
    }
}
   