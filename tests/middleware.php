<?php

interface Milldeware {
    public static function handle(Closure $next);
}

class VerfiyCsrfToekn implements Milldeware {

    public static function handle(Closure $next)
    {
        echo '验证csrf Token <br>';
        $next();
    }
}

class VerfiyAuth implements Milldeware {

    public static function handle(Closure $next)
    {
        echo '验证是否登录 <br>';
        $next();
    }
}

class SetCookie implements Milldeware {
    public static function handle(Closure $next)
    {
        $next();
        echo '设置cookie信息！ <br>';
    }
}

function getSlice()
{
    return function($stack, $pipe)
    {
        return function() use ($stack, $pipe)
        {
            return $pipe::handle($stack);
        };
    };
}

function then() {

    $calls = [
        'VerfiyCsrfToekn',
        'VerfiyAuth',
        'SetCookie'
    ];

    $handle = function(){
        echo '当前要执行的程序! <br>';
    };

    call_user_func(array_reduce($calls, getSlice(), $handle));
}

then();