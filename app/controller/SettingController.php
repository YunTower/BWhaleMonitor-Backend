<?php

namespace app\controller;

use app\model\Config;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use support\Db;
use support\Log;
use support\Request;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Response;

class SettingController
{
    protected $noNeedLogin = ['install', 'get'];

    public function install(Request $request): Response
    {
        try {
            /**
             * 检测是否有安装完成
             */
            if (check_install()) {
                throw new Exception('系统已安装，请勿重复操作');
            } else {
                /**
                 * 检测是否有未安装完成导致残留的数据表
                 */
                try {
                    $check_tables = check_tables_existence('any');
                    if ($check_tables) {
                        throw new Exception('检测到数据表残留，请先删除残留的数据表后再安装');
                    }
                } catch (Exception $e) {
                    Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
                    return serverError($e->getMessage());
                }
            }

            /**
             * 验证参数
             */
            try {
                $v = v::input($request->post(), [
                    'title' => v::notEmpty()->length(4, 50)->setName('title'),
                    'username' => v::notEmpty()->length(8, 50)->setName('username'),
                    'password' => v::notEmpty()->length(8, 50)->setName('password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            /**
             * 创建数据表
             */
            try {
                Db::schema()->create('yt_monitor_server', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->string('os');
                    $table->string('ip');
                    $table->string('location');
                    $table->string('cpu');
                    $table->string('memory');
                    $table->string('disk');
                    $table->string('status');
                    $table->string('uptime');
                    $table->timestamps();
                });
                Db::schema()->create('yt_monitor_log', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('server_id');
                    $table->string('name');
                    $table->string('value');
                    $table->string('created');
                });
                Db::schema()->create('yt_monitor_config', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->string('value')->nullable();
                    $table->timestamps();
                });
            } catch (QueryException $e) {
                Log::error($e->getMessage(), ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode()]);
                return serverError('数据表创建失败：' . $e->getMessage());
            }

            /**
             * 检查数据表是否存在
             */
            $check_tables = check_tables_existence('all', true);
            if (!$check_tables || is_array($check_tables)) {
                throw new Exception('数据表' . $check_tables[0] . '缺失，请检查数据库配置或重新安装');
            }

            /**
             * 写入初始数据
             */
            $v['password'] = password_hash($v['password'], PASSWORD_DEFAULT);
            $data = $v + [
                    'interval' => 5,
                    'visitor' => false,
                    'visitor_password' => null
                ];
            foreach ($data as $key => $value) {
                Config::create(['name' => $key, 'value' => $value]);
            }

            /**
             * 生成锁定文件
             */
            $lock_file = __DIR__ . '/../../install.lock';
            $lock_data = [
                'time' => time()
            ];
            file_put_contents($lock_file, json_encode($lock_data));

            return success('', Config::get());
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
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
                    'visitor' => v::boolVal()->setName('visitor'),
                    'visitor_password' => v::nullable(v::length(8, 50))->setName('visitor_password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            Config::updateOrInsert($v);
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

            $configs = [];
            $openColumns = ['title', 'interval', 'visitor', 'visitor_password'];

            $columns = explode(',', $v['columns']);
            foreach ($columns as $column) {
                $_column = Config::find($column);
                if (!$_column) continue;
                if (!in_array($column, $openColumns) && $request->user['role'] != 'admin') continue;
                if ($column == 'visitor_password') {
                    $value = $_column->value;
                    $configs[$column] = !(($value == null));
                } else {
                    $configs[$column] = $_column->value;
                }
            }
            return success('success', $configs);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }
}
   