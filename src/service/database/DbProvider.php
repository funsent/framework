<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db;

use funsent\db\Db;
use funsent\application\contract\Provider;

/**
 * 数据库服务提供者
 * @package funsent\db
 */
class DbProvider extends Provider
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
        $this->application->bind('Db', function () {
            return new Db();
        });
    }
}
