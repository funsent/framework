<?php

/**
 * funsent - A PHP Framework For Web Application
 *
 * @link      http://www.funsent.com/
 * @copyright 2020 funsent.com, Inc.
 * @author    yanggf <2018708@qq.com>
 * @package   funsent
 * @version   1.1.2
 */

namespace funsent\view;

use funsent\contract\Provider;

/**
 * 视图服务提供者
 * 
 * @package funsent\view
 */
class ViewProvider extends Provider
{
    /**
     * 延迟加载标识
     * 
     * @var bool
     */
    public $defer = false;

    /**
     * 注册服务时执行的方法
     * 
     * @return void
     */
    public function register()
    {
        $this->app->singleton('View', function(){
            return new View();
        });
    }

    /**
     * 启用服务时执行的方法
     * 
     * @return void
     */
    public function boot()
    {
    }
}
