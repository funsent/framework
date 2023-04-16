<?php
/**
 * Created by PhpStorm.
 * User: yanggf
 * Date: 2019/6/27
 * Time: 12:30
 */

interface Middleware
{
    public static function handle(\Closure $next);
}

class VerifyCsrfToken implements Middleware
{
    public static function handle(\Closure $next)
    {
        echo '验证Csrf-Token <br />';
        $next();
    }
}

class ShareErrorsFromSession implements Middleware
{
    public static function handle(\Closure $next)
    {
        echo '如果session中有errors变量，则共享它<br />';
        $next();
    }
}

class StartSession implements Middleware
{
    public static function handle(\Closure $next)
    {
        echo '开启session,获取数据<br />';
        $next();
        echo '保存session数据，关闭session<br />';
    }
}

class AddQueuedCookiesToResponse implements Middleware
{
    public static function handle(\Closure $next)
    {
        $next();
        echo '添加下一次请求需要的cookie<br />';
    }
}

class EncryptCookies implements Middleware
{
    public static function handle(\Closure $next)
    {
        echo '对输入请求的cookie进行解密<br />';
        $next();
        echo '对输出响应的cookie进行加密<br />';
    }
}

class CheckForMaintenanceMode implements Middleware
{
    public static function handle(\Closure $next)
    {
        echo '确定当前程序是否处于维护状态<br />';
        $next();
    }
}


function getSlice()
{
    return function ($stack, $pipe) {
        return function () use ($stack, $pipe) {
            return $pipe::handle($stack);
        };
    };
}

function firstSlice()
{
    return function () {
        echo '请求向路由传递，返回响应<br />';
    };
}

function then()
{
    $pipes = [
        CheckForMaintenanceMode::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class
    ];

    $pipes = array_reverse($pipes);
    $user = array_reduce($pipes, getSlice(), firstSlice());
    call_user_func($user);
}

then();
