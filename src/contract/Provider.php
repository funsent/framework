<?php

/**
 * funsent - the web application framework by PHP
 * Copyright(c) funsent.com Inc. All Rights Reserved.
 * 
 * @version $Id$
 * @author  yanggf <2018708@qq.com>
 * @see     http://www.funsent.com/
 * @license MIT
 */

declare(strict_types=1);

namespace funsent\contract;

use BadMethodCallException;

/**
 * 服务提供者抽象类
 */
abstract class Provider
{
    /**
     * 延迟加载
     * 
     * @var bool
     */
    protected $defer = false;

    /**
     * 应用实例
     * 
     * @var \funsent\app\App
     */
    protected $app;

    /**
     * 构造函数，设置app属性
     * 
     * @param \funsent\app\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 注册服务时执行的方法
     * 
     * @return mixed
     */
    abstract function register();

    /**
     * 判断是否延迟加载服务
     * 
     * @return boolean
     */
    public function isDeferred()
    {
        return $this->defer;
    }

    /**
     * 调用不存在的实例方法时触发
     * 
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'boot') {
            return;
        }
        throw new BadMethodCallException('Call to undefined method: ' . $method . '()');
    }
}
