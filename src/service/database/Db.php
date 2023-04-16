<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db;

use funsent\config\Config;
use funsent\db\query\Query;

/**
 * 数据库服务
 * @package funsent\db
 */
class Db
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
            $config = Config::except('database', ['write', 'read']);
            if (empty($config['write'])) {
                $config['write'][] = Config::except('database', ['write', 'read']);
            }
            if (empty($config['read'])) {
                $config['read'][] = Config::except('database', ['write', 'read']);
            }
            Config::set('database', $config);
            self::$instance = new Query();
            self::$instance->connection();
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
