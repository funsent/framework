<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\support\captcha;

use funsent\kernel\application\build\Facade;

/**
 * 验证码服务门面
 * @package funsent\support\captcha
 */
class CaptchaFacade extends Facade
{
    /**
     * 获取门面
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'Captcha';
    }
}
