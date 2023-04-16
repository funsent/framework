<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\console;

use funsent\console\Console;
use funsent\application\contract\Provider;

/**
 * 控制台服务提供者
 * @package funsent\console
 */
class ConsoleProvider extends Provider
{
    /**
     * 延迟加载
     * @var boolean
     */
    public $defer = false;

    /**
     * 启动服务
     * @return void
     */
    public function boot()
    {
        if (php_sapi_name() == 'cli') {
            die(Console::run());
        }
    }

    /**
     * 注册服务
     * @return void
     */
    public function register()
    {
        $this->application->singleton('Console', function () {
            return new Console();
        });
    }
}
