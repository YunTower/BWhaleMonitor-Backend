<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        if (!check_install() && !$request->path() == 'install') {
            return forbidden('系统未安装');
        }
        return $next($request);
    }
}
