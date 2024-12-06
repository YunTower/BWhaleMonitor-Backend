<?php

namespace app\controller;

use app\model\Config;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Db;
use support\Log;
use support\Request;
use support\Response;
use Workerman\Http\Client;
use WpOrg\Requests\Requests;

class InstallController
{
    protected $noNeedLogin = ['install', 'envCheck'];

    public function envCheck(Request $request): Response
    {
        try {
            $missing = [];

            // 检查PHP版本号是否大于7.2
            if (version_compare(PHP_VERSION, '7.2.0', '<')) {
                $missing[] = ['name' => 'PHP >= 7.2.0', 'type' => 'env', 'status' => false];
            } else {
                $missing[] = ['name' => 'PHP >= 7.2.0', 'type' => 'env', 'status' => true];
            }

            // 检查 openssl 扩展是否已安装
            if (!extension_loaded('openssl')) {
                $missing[] = ['name' => 'ext-openssl', 'type' => 'php-ext', 'status' => false];
            } else {
                $missing[] = ['name' => 'ext-openssl', 'type' => 'php-ext', 'status' => true];
            }

            // 检查 json 扩展是否已安装
            if (!extension_loaded('json')) {
                $missing[] = ['name' => 'ext-json', 'type' => 'php-ext', 'status' => false];
            } else {
                $missing[] = ['name' => 'ext-json', 'type' => 'php-ext', 'status' => true];
            }

            // 检查 gd 扩展是否已安装
            if (!extension_loaded('gd')) {
                $missing[] = ['name' => 'ext-gd', 'type' => 'php-ext', 'status' => false];
            } else {
                $missing[] = ['name' => 'ext-gd', 'type' => 'php-ext', 'status' => true];
            }

            // 检测是否有未安装的扩展
            $missing_extensions = array_filter($missing, function ($item) {
                return $item['status'] === false;
            });

            if ($missing_extensions) {
                return response_json(200, $missing, 400, '缺少必要的扩展');
            } else {
                return success('环境检测通过');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

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
                    'password' => v::notEmpty()->setName('password')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            /**
             * 创建数据表
             */
//            try {
//                Db::schema()->create('yt_monitor_server', function (Blueprint $table) {
//                    $table->increments('id');
//                    $table->string('name');
//                    $table->string('os');
//                    $table->string('ip');
//                    $table->string('location')->nullable();
//                    $table->string('cpu')->nullable();
//                    $table->string('memory')->nullable();
//                    $table->string('disk')->nullable();
//                    $table->string('key');
//                    $table->string('status');
//                    $table->string('uptime');
//                    $table->timestamps();
//                });
//                Db::schema()->create('yt_monitor_log', function (Blueprint $table) {
//                    $table->increments('id');
//                    $table->string('server_id');
//                    $table->string('name');
//                    $table->string('value');
//                    $table->string('created');
//                });
//                Db::schema()->create('yt_monitor_config', function (Blueprint $table) {
//                    $table->increments('id');
//                    $table->string('name');
//                    $table->string('value')->nullable();
//                    $table->timestamps();
//                });
//            } catch (QueryException $e) {
//                Log::error($e->getMessage(), ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode()]);
//                return serverError('数据表创建失败：' . $e->getMessage());
//            }

            /**
             * 检查数据表是否存在
             */
//            $check_tables = check_tables_existence('all', true);
//            if (!$check_tables || is_array($check_tables)) {
//                throw new Exception('数据表' . $check_tables[0] . '缺失，请检查数据库配置或重新安装');
//            }

            /**
             * 写入初始数据
             */
//            $v['password'] = password_hash($v['password'], PASSWORD_DEFAULT);
//            $data = $v + [
//                    'interval' => 5,
//                    'visitor' => false,
//                    'visitor_password' => null
//                ];
//            foreach ($data as $key => $value) {
//                Config::create(['name' => $key, 'value' => $value]);
//            }

            /**
             * 获取本机IP地址
             */
            if (!$hostname = gethostname()) {
                return badRequest('获取服务器名称失败');
            }

            try {
                $response = Requests::get(config('app.api.host'));
                $host = $response->body;
            } catch (Exception $e) {
                Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
                return serverError($e->getMessage());
            }

            /**
             * 使用Openssl生成公钥和私钥
             */
            $config = [
                "digest_alg" => "sha512",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            if (!$res) {
                throw new Exception('生成公钥和私钥失败');
            }
            openssl_pkey_export($res, $private_key);
            $public_key = openssl_pkey_get_details($res);
            $public_key = $public_key["key"];


            /**
             * 生成锁定文件
             */
            $lock_file = __DIR__ . '/../../install.lock.json';
            $lock_data = [
                'time' => time(),
                'host' => $host,
                'hostname' => $hostname,
                'public_key' => $public_key,
                'private_key' => $private_key
            ];
            file_put_contents($lock_file, json_encode($lock_data));

            return success('', Config::get());
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }
}