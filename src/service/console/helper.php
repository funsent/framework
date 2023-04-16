<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

use funsent\console\Console;

/**
 * 助手函数
 */

if (!function_exists('console')) {
    /**
     * 执行命令行命令
     * @param string $command
     * @return mixed
     */
    function console($command)
    {
        $_SERVER['argv'] = preg_split('/\s+/', $command);
        return Console::bootstrap(true);
    }
}
