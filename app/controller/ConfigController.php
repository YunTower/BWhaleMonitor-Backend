<?php

namespace app\controller;

use app\model\Config;
use Exception;
use support\Log;
use support\Request;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Response;

class ConfigController
{
    protected array $noNeedLogin = ['get'];

    public function save(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'title' => v::nullable(v::length(0, 50)->setDefault('蓝鲸服务器探针'))->setName('title'),
                    'interval' => v::intVal()->between(1, 60)->setName('interval'),
                    'visitor' => v::boolVal()->setName('visitor'),
                    ' ' => v::nullable(v::length(6, 50))->setName('visitor_password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if ($v['visitor'] && (!$v['visitor_password'] || strlen($v['visitor_password']) < 6)) {
                return badRequest('访客密码不能少于6位');
            }

            foreach ($v as $key => $value) {
                Config::updateOrInsert(['name' => $key, 'value' => $value]);
            }
            return success();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function get(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->get(), [
                    'columns' => v::notEmpty()->stringType()->setName('columns'),
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            $user = $request->user;
            $configs = [];
            $openColumns = ['title', 'interval', 'visitor', 'visitor_password'];

            $columns = explode(',', $v['columns']);
            foreach ($columns as $column) {
                $_column = Config::find($column);
                if (!$_column) continue;
                if (!in_array($column, $openColumns) && (!$user || $request->user['role'] != 'admin')) continue;
                if ($column == 'password') {
                    $configs[$column] = str_repeat('*', strlen($_column->value) / 3);
                    continue;
                }
                if ($column == 'visitor_password') {
                    $value = $_column->value;
                    $configs[$column] = !(($value == null));
                    continue;
                }
                if ($column == 'visitor') $_column->value = !($_column->value === '0');
                $configs[$column] = $_column->value;
            }
            return success('success', $configs);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function editUsername(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'old_username' => v::notEmpty()->length(8, 50)->setName('old_username'),
                    'new_username' => v::notEmpty()->length(8, 50)->setName('new_username'),
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (!$user = Config::where('name', 'username')->first()) {
                return notFound('配置不存在');
            }

            if ($user->value != $v['old_username']) {
                return badRequest('旧用户名错误');
            }

            $user->value = $v['new_username'];
            $user->save();
            return success('修改成功', ['username' => $user->value]);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function editPassword(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'old_password' => v::notEmpty()->setName('old_password'),
                    'new_password' => v::notEmpty()->setName('new_password'),
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (!$user = Config::where('name', 'password')->first()) {
                return notFound('配置不存在');
            }

            if (!password_verify($v['old_password'], $user->value)) {
                return badRequest('旧密码错误');
            }

            $user->value = password_hash($v['new_password'], PASSWORD_DEFAULT);
            $user->save();
            return success('修改成功');
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }
}
   