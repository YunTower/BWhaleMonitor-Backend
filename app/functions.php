<?php

use support\Response;

//json
function response_json($status, $data = null, $code = 0, $msg = 'success'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]));
}

// 404
function notFound($msg = 'Not Found 资源不存在'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => 1404, 'msg' => $msg]));
}

// 500
function serverError($msg = 'Server Error 服务器错误'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => 1500, 'msg' => $msg]));
}

// 403
function forbidden($msg = 'Forbidden 禁止访问'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => 1403, 'msg' => $msg]));
}

// 401
function unauthorized($msg = 'Unauthorized 未授权'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => 1401, 'msg' => $msg]));
}

// 400
function badRequest($msg = 'Bad Request 请求错误'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['code' => 1400, 'msg' => $msg]));
}

// success
function success($msg = 'success', $data = null, $header = []): Response
{
    return new Response(200, ['Content-Type' => 'application/json'] + $header, json_encode(['code' => 0, 'msg' => $msg, 'data' => $data]));
}
