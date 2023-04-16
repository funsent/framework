<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\db\Db;

/**
 * 助手函数
 */

if (!function_exists('db')) {
    /**
     * 获取数据库服务实例
     * @param string $table
     * @return \funsent\db\Db
     */
    function db($table = null)
    {
        return is_null($table) ? Db::singleton() : Db::table($table);
    }
}