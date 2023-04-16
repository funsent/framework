<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\config\Config;
use funsent\schema\Schema;

/**
 * 助手函数
 */

if (!function_exists('schema')) {
    /**
     * 获取数据库模式服务实例
     * @return object
     */
    function schema()
    {
        return Schema::singleton();
    }
}

if (!function_exists('table')) {
    /**
     * 获取数据表全名
     * @param string $table
     * @return string
     */
    function table($table)
    {
        return Config::get('db.prefix') . $table;
    }
}