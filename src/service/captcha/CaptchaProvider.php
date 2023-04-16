<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\support\captcha;

use funsent\kernel\application\build\Provider;

/**
 * 验证码服务提供者
 * @package funsent\support\captcha
 */
class CaptchaProvider extends Provider
{
    /**
     * 延迟加载标识
     * @var boolean
     */
    public $defer = true;
    
    /**
     * 服务运行时自动执行的方法
     * @return void
     */
    public function boot()
    {
    }

    /**
     * 服务注册时自动执行的方法
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Captcha', function () {
            return new Captcha();
        });
    }
}
