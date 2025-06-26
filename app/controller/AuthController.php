<?php

namespace app\controller;

use app\model\Config;
use Exception;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Log;
use support\Request;
use support\Response;
use Tinywan\Jwt\JwtToken;
use Webman\Captcha\CaptchaBuilder;

class AuthController
{

    protected array $noNeedLogin = ['captcha', 'admin', 'visitor'];

    public function captcha(Request $request): Response
    {
        // 初始化验证码类
        $builder = new CaptchaBuilder(6);
        // 生成验证码
        $builder->build();
        // 将验证码的值存储到session中
        $request->session()->set('captcha', strtolower($builder->getPhrase()));
        // 获得验证码图片二进制数据
        $img_content = $builder->get();
        // 输出验证码二进制数据
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

    public function admin(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'username' => v::notEmpty()->length(8, 50)->setName('username'),
                    'password' => v::notEmpty()->setName('password'),
                    'captcha' => v::notEmpty()->length(6, 6)->setName('captcha')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (strtolower($v['captcha']) !== $request->session()->get('captcha')) {
                return badRequest('验证码错误');
            }

            if (Config::find('username')->value != $v['username']) {
                return badRequest('账号不存在');
            }

            if (!password_verify($v['password'], Config::find('password')->value)) {
                return badRequest('密码错误');
            }

            $token = JwtToken::generateToken([
                'id' => time(),
                'username' => $v['username'],
                'ip' => $request->getRealIp()
            ]);

            return success('success', [['user' => ['username' => $v['username'], 'routes' => $this->routes()]],
                ['access_token' => $token['access_token'], 'refresh_token' => $token['refresh_token']]);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function visitor(Request $request): Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'password' => v::nullable(v::length(8, 50))->setName('password'),
                    'captcha' => v::notEmpty()->length(6, 6)->setName('captcha')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (strtolower($v['captcha']) !== $request->session()->get('captcha')) {
                return badRequest('验证码错误');
            }

            if (Config::find('visitor')->value != 1) {
                return badRequest('管理员未开启游客访问功能');
            }

            $need_password = !((Config::find('visitor_password')->value == null));
            if ($need_password && !preg_match('/^(?=.*[a-zA-Z])(?=.*\d).{8,50}$/', $v['password'])) {
                return badRequest('密码错误');
            }

            if (Config::find('visitor_password')->value != $v['password']) {
                return badRequest('密码错误');
            }

            Log::info('访客【' . $request->getRealIp() . '】于' . date('Y-md-m-Y H-i-s') . '登录了系统');

            $username = '访客 ' . $request->getRealIp();
            $token = JwtToken::generateToken([
                'id' => time(),
                'username' => $username,
                'ip' => $request->getRealIp()
            ]);

            return success('success', [['user' => ['username' => $username, 'routes' => $this->routes()]],
                ['access_token' => $token['access_token'], 'refresh_token' => $token['refresh_token']]);
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function check(Request $request): Response
    {
        try {
            $user = $request->user;

            var_dump($user);
            if ($user['role'] === 'admin') {
                var_dump($user['username']);
                $_user = Config::where('username', $user['username'])->first();
                var_dump($_user);
                if (!$_user) {
                    return unauthorized('无效的Token');
                }

                var_dump($_user);
                return success('success', ['user' => ['username' => $_user->value, 'role' => $user['role']], 'routes' => $this->routes()]);
            }

            if ($user['role'] === 'visitor') {
                return success('success', ['user' => ['username' => $user['username'], 'role' => $user['role']], 'routes' => $this->routes()]);
            }

            return unauthorized();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return unauthorized();
        }
    }

    public function logout(Request $request): Response
    {
        if (!$request->cookie('token')) {
            return unauthorized();
        }

        $response = response();
        $response->withHeaders([
            'Content-Type' => 'application/json',
        ]);
        $response->cookie('token', '', -1, '/', '', false, true);
        $response->withBody(json_encode(['code' => 0, 'msg' => 'success']));
        return $response;
    }

    public function routes(): array
    {
        $menu_list[] = [
            "path" => "/manager",
            "name" => "manager",
            "meta" => [
                "title" => "管理",
            ],
            "children" => [
                [
                    "path" => "server",
                    "name" => "manager-server-index",
                    "component" => "/manager/server/index",
                    "meta" => [
                        "title" => "服务器管理",
                        "icon" => "ServerOutline"
                    ]
                ],
                [
                    "path" => "server/details/:id",
                    "name" => "manager-server-details",
                    "component" => "/manager/details/index",
                    "meta" => [
                        "title" => "服务器详情",
                        "show" => false
                    ]
                ],
                [
                    "path" => "setting",
                    "name" => "manager-setting",
                    "component" => "/manager/setting/index",
                    "meta" => [
                        "title" => "系统设置",
                        "icon" => "SettingsOutline"
                    ]
                ],
            ]
        ];

        return $menu_list;
    }
}
