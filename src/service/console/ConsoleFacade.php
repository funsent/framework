<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\config;

use funsent\application\contract\Facade;

/**
 * 控制台服务门面
 * @package funsent\config
 */
class ConsoleFacade extends Facade
{
    /**
     * 获取门面
     * @return string
     */
	public static function getFacadeAccessor()
    {
		return 'Console';
	}
}
