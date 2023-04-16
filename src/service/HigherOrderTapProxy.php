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
 * 高级tap代理
 */
class HigherOrderTapProxy
{
    /**
     * 被Tap的目标
     *
     * @var mixed
     */
    public $target;

    /**
     * 创建一个新的Tap实例
     *
     * @param mixed $target
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * 动态传递方法调用目标
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);
        return $this->target;
    }
}
