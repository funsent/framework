<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\validater;

use funsent\validater\build\Base;

/**
 * 验证器服务
 * @package funsent\validater
 */
class Validater
{
    /**
     * 验证常量
     */
    const EXISTS_VALIDATE       = 1; // 存在字段时验证
    const VALUE_VALIDATE        = 2; // 值不为空时验证
    const MUST_VALIDATE         = 3; // 必须验证
    const VALUE_NULL            = 4; // 值为空时验证
    const NO_EXISTS_VALIDATE    = 5; // 不存在字段时验证

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
