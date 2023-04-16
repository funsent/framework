<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\validater;

use funsent\application\contract\Facade;

/**
 * 验证器服务门面
 * @package funsent\validater
 */
class ValidaterFacade extends Facade
{
    /**
     * 获取门面
     * @return string
     */
	public static function getFacadeAccessor()
    {
		return 'Validater';
	}
}
