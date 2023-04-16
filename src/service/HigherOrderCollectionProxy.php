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

namespace funsent\support;

/**
 * 高级集合代理
 */
class HigherOrderCollectionProxy
{
    /**
     * 正在操作的集合
     *
     * @var \funsent\support\Collection
     */
    protected $collection;

    /**
     * 被代理的方法
     *
     * @var string
     */
    protected $method;

    /**
     * 创建新的代理实例
     *
     * @param \funsent\support\Collection $collection
     * @param string $method
     * @return void
     */
    public function __construct(Collection $collection, $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * 代理访问集合项上的属性
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * 代理对集合项的方法调用
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
