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

use InvalidArgumentException;
use funsent\exception\DriverNotFoundException;
use funsent\foundation\App;
use funsent\helper\Str;

abstract class DriverManager
{
    protected $app;

    protected $namespace = '';

    protected $instances = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 获取驱动实例
     * @param null|string $type
     * @return mixed
     */
    protected function driver(string $type = '')
    {
        $type = $type ?: $this->getDefaultDriver();

        if (is_null($type)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->instances[$type] = $this->getDriver($type);
    }

    /**
     * 获取驱动实例
     * @param string $type
     * @return mixed
     */
    protected function getDriver(string $type)
    {
        return $this->instances[$type] ?? $this->createDriver($type);
    }

    /**
     * 获取驱动类型
     * @param string $type
     * @return mixed
     */
    protected function resolveType(string $type)
    {
        return $type;
    }

    /**
     * 获取驱动配置
     * @param string $key
     * @return mixed
     */
    protected function resolveConfig(string $key)
    {
        return $key;
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass(string $type): string
    {
        if (false !== strpos($type, '\\')) {
            if (class_exists($type)) {
                return $type;
            }
        }

        $class = $this->namespace . Str::studly($type);
        if (class_exists($class)) {
            return $class;
        }

        throw new DriverNotFoundException(sprintf('driver [%s] not found', $type));
    }

    /**
     * 获取驱动参数
     * @param $name
     * @return array
     */
    protected function resolveParams($name): array
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 创建驱动
     *
     * @param string $type
     * @return mixed
     *
     */
    protected function createDriver(string $type)
    {
        $params = $this->resolveParams($type);
        $type = $this->resolveType($type);

        $method = 'create' . Str::studly($type) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        $class = $this->resolveClass($type);

        return $this->app->invokeClass($class, $params);
    }

    /**
     * 移除一个驱动实例
     *
     * @param array|string|null $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name = $name ?? $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     * @return string|null
     */
    abstract public function getDefaultDriver();

    /**
     * 动态调用
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
