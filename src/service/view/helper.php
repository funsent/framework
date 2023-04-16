<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\kernel\view\View;
use funsent\kernel\session\Session;

if (!function_exists('view')) {
    /**
     * 视图服务助手
     * @param string $file 视图文件
     * @param mixed $vars 视图变量
     * @return \funsent\kernel\request\Request
     */
    function view($file = '', $vars = [])
    {
        return View::make($file, $vars);
    }
}

if (!function_exists('widget')) {
    /**
     * 页面组件
     * @return string
     */
    function widget()
    {
        $vars = func_get_args();
        $info = preg_split('@[\./]@', array_shift($vars));
        $method = array_pop($info);
        $className = array_pop($info);
        $class = implode('\\', $info) . '\\' . ucfirst($className);
        return call_user_func_array([new $class, $method], $vars);
    }
}

if (!function_exists('view_path')) {
    /**
     * 视图目录
     * @return string
     */
    function view_path()
    {
        return View::path();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 生成CSRF表单隐藏项
     * @return string
     */
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . Session::get('csrf_token') . '" />';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取CSRF表单令牌
     * @return string
     */
    function csrf_token()
    {
        return Session::get('csrf_token');
    }
}

if (!function_exists('method_field')) {
    /**
     * 生成伪造请求方法表单隐藏项
     * @param string $type
     * @return string
     */
    function method_field($type)
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($type) . '" />';
    }
}
