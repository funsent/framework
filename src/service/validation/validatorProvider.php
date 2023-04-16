<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\validater;

use funsent\validater\Validater;
use funsent\application\contract\Provider;

/**
 * 验证器服务提供者
 * @package funsent\validater
 */
class ValidaterProvider extends Provider
{
    /**
     * 延迟加载
     * @var boolean
     */
    public $defer = true;

    /**
     * 启动服务
     * @return void
     */
    public function boot()
    {
    }

    /**
     * 注册服务
     * @return void
     */
    public function register()
    {
        $this->application->singleton('Validater', function () {
            return new Validater();
        });
    }
}
