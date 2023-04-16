<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\service\{{lower_name}};

use funsent\application\contract\Facade;

/**
 * {{name}} 服务门面
 * @package {{app}}\common\service\{{lower_name}}
 */
class {{name}}Facade extends Facade
{
    /**
     * 获取门面存取器
     * @return string
     */
	public static function getFacadeAccessor()
    {
		return '{{name}}';
	}
}
