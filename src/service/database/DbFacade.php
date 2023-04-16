<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db;

use funsent\application\contract\Facade;

/**
 * 数据库服务门面
 * @package funsent\db
 */
class DbFacade extends Facade
{
    /**
     * 获取门面
     * @return string
     */
	public static function getFacadeAccessor()
    {
		return 'Db';
	}
}
