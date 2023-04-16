<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\schema\build;

use Closure;
use funsent\config\Config;
use funsent\schema\Schema;
use funsent\schema\build\Blueprint;

/**
 * 数据库模式服务实现
 * @package funsent\schema
 */
class Base
{
    /**
     * 数据表
     * @var string
     */
    protected $table;

    /**
     * 动作
     * @var string
     */
    protected $exe;

    /**
     * 驱动
     * @var object
     */
    protected $driver;

    /**
     * 构造方法
     * 初始化驱动
     */
    public function __construct()
    {
        $class = '\\funsent\\schema\\driver\\' . ucfirst(Config::get('db.driver'));
        $this->driver = new $class();
    }

    /**
     * 创建表
     * @param string $table
     * @param Closure $callback
     * @param string $comment 表注释
     * @return boolean
     */
    public function create($table, Closure $callback, $comment = '')
    {
        if (Schema::tableExists($table)) {
            return true;
        }
        $blueprint = new Blueprint($table, 'create', $comment);
        $callback($blueprint);
        return $blueprint->create();
    }

    /**
     * 修改表
     * @param string $table
     * @param Closure $callback
     * @return array
     */
    public function alter($table, Closure $callback)
    {
        $Blueprint = new Blueprint($table, 'alert');
        $callback($Blueprint);
    }

    /**
     * 调用不存在的实例方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->driver, $method], $parameters);
    }
}
