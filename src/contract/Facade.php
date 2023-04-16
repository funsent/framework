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

use RuntimeException;

/**
 * 服务门面抽象类
 * 
 * @method string setFacadeApp(\funsent\app\App $app)
 * @method \funsent\app\App getFacadeApp()
 * @method object getFacadeInstance()
 * @method void clearResolvedInstance(string $name)
 * @method void clearResolvedInstances()
 * @method mixed static::__callStatic(string $method, array $args)()
 */
abstract class Facade
{
    /**
     * 应用实例
     * 
     * @var \funsent\app\App
     */
    protected static $app;

    /**
     * 解析过的服务实例
     * 
     * @var array
     */
    protected static $resolvedInstance = [];

    /**
     * 设置应用实例
     * 
     * @param \funsent\app\App $app
     * @return void
     */
    public static function setFacadeApp($app)
    {
        static::$app = $app;
    }

    /**
     * 获取应用实例
     * 
     * @return \funsent\app\App
     */
    public static function getFacadeApp()
    {
        return static::$app;
    }

    /**
     * 获取绑定的服务
     * 
     * @return object
     */
    public static function getFacadeInstance()
    {
        return self::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * 获取已注册服务门面名称
     * 
     * @return object|string
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * 解析服务
     * 
     * @param object|string $name
     * @return object
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        return static::$resolvedInstance[$name] = static::$app[$name];
    }

    /**
     * 移除服务
     * 
     * @param string $name
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * 移除所有服务
     * 
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * 静态调用服务的实例方法
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        if (!$instance = static::getFacadeInstance()) {
            throw new RuntimeException('A facade instance has not been set.');
        }
        return $instance->$method(...$args);
    }
}
