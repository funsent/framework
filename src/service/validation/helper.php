<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\validater\Validater;

/**
 * 助手函数
 */

if (!function_exists('validater')) {
    /**
     * 获取验证服务实例
     * @return object
     */
    function validater()
    {
        return Validater::singleton();
    }
}
