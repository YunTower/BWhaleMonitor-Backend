<?php

namespace app\controller;

use app\model\Config;
use Exception;
use http\Cookie;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use support\Db;
use support\Log;
use support\Request;
use Webman\Captcha\CaptchaBuilder;

class AuthController
{

    public function captcha(Request $request): \support\Response
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

    public function admin(Request $request): \support\Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'username' => v::notEmpty()->length(8, 50)->setName('username'),
                    'password' => v::notEmpty()->length(8, 50)->setName('password'),
                    'captcha' => v::notEmpty()->length(6, 6)->setName('captcha')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (strtolower($v['captcha']) !== $request->session()->get('captcha')) {
                return badRequest('验证码错误');
            }

            if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).{8,50}$/', $v['password'])) {
                return badRequest('密码错误');
            }

            if (Config::find('username')->value != $v['username']) {
                return badRequest('账号不存在');
            }

            if (!password_verify($v['password'], Config::find('password')->value)) {
                return badRequest('密码错误');
            }

            setcookie('login', true, time() + 60 * 60 * 24 * 15, '/', '', false, true);
            setcookie('role', 'admin', time() + 60 * 60 * 24 * 15, '/', '', false, true);

            return success();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }

    public function visitor(Request $request): \support\Response
    {
        try {
            try {
                $v = v::input($request->post(), [
                    'password' => v::notEmpty()->length(8, 50)->setName('password'),
                    'captcha' => v::notEmpty()->length(6, 6)->setName('captcha')
                ]);
            } catch (ValidationException $e) {
                return badRequest($e->getMessage());
            }

            if (strtolower($v['captcha']) !== $request->session()->get('captcha')) {
                return badRequest('验证码错误');
            }

            if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).{8,50}$/', $v['password'])) {
                return badRequest('密码错误');
            }

            if (Config::find('visitor')->value != 1) {
                return badRequest('管理员未开启游客访问功能');
            }

            if (Config::find('visitor_password')->value != $v['password']) {
                return badRequest('密码错误');
            }

            Log::info("访客");
            setcookie('login', true, time() + 60 * 60 * 24 * 15, '/', '', false, true);
            setcookie('role', 'visitor', time() + 60 * 60 * 24 * 15, '/', '', false, true);

            return success();
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['error' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()]);
            return serverError($e->getMessage());
        }
    }
}
