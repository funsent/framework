<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\middleware;

use Closure;
use funsent\middleware\contract\MiddlewareInterface;

/**
 * {{name}}中间件
 * @package {{app}}\common\middleware
 */
class {{name}} implements MiddlewareInterface
{
	/**
	 * 执行
	 * @param \Closure
	 */
	public function handle($next)
    {
         // do something ...
         $next();
	}
}
