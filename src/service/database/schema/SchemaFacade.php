<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\schema;

use funsent\application\contract\Facade;

/**
 * 数据库模式服务门面
 * @package funsent\schema
 */
class SchemaFacade extends Facade
{
    /**
     * 获取门面
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'Schema';
    }
}
