<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\schema;

use funsent\schema\build\Base;

/**
 * 数据库模式服务
 * @package funsent\schema
 */
class Schema
{
    /**
     * 实现类实例
     * @var object
     */
    protected static $instance;

    /**
     * 创建单例对象
     * @return object
     */
    public static function singleton()
    {
        if (!self::$instance) {
            self::$instance = new Base();
        }
        return self::$instance;
    }

    /**
     * 调用不存在的实例方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([self::singleton(), $method], $parameters);
    }

    /**
     * 调用不存在的静态方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::singleton(), $method], $parameters);
    }
}
