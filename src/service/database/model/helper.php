<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\application\Application;

/**
 * 助手函数
 */

if (!function_exists('model')) {
    /**
     * 获取模型实例
     * @param string $name
     * @return \funsent\model\Model
     */
    function model($name)
    {
        $class = '\\app\\home\\model\\' . ucfirst($name);
        return Application::make($class);
    }
}
