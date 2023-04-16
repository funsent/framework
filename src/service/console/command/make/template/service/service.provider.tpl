<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\service\{{lower_name}};

use funsent\application\contract\Provider;

/**
 * {{name}} 服务提供者
 * @package {{app}}\common\service\{{lower_name}}
 */
class {{name}}Provider extends Provider
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
		$this->application->singleton('{{name}}', function ($app) {
			return new {{name}}($app);
		});
	}
}
