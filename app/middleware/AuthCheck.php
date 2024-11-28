<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $controller = new ReflectionClass($request->controller);
        $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];

        if (!check_install() && !$request->path() == 'install') {
            return response_json(200, null, 1501, '系统未安装');
        }

        /**
         * 不需要登录则直接放行
         */
        if (in_array($request->action, $noNeedLogin)) {
            return $next($request);
        }

        if (!$request->cookie('token')) {
            return unauthorized();
        }

        $user = json_decode(base64_decode($request->cookie('token')), true);
        if (isset($user['exp']) && $user['exp'] < time()) {
            return unauthorized('登录状态已失效');
        }

        if (isset($user['ip']) && ($user['ip'] != $request->getRealIp())) {
            return unauthorized('未授权的IP地址');
        }

        $request->token = $request->cookie('token');
        $request->user = $user;
        return $next($request);
    }
}
