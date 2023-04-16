<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\console\build;

use Closure;
use funsent\console\build\Output;
use funsent\error\exception\Exception;

/**
 * 命令行服务实现
 * @package funsent\console\build
 */
class Base
{
    use Output;

    /**
     * 绑定的命令
     * @var array
     */
    public $binds = [];

    /**
     * 执行命令运行
     * @return mixed
     */
    public function run()
    {
        // 移除脚本名
        array_shift($_SERVER['argv']);

        // 取出命令和行为
        $info = explode(':', array_shift($_SERVER['argv']));
        if (isset($this->binds[$info[0]])) {
            $class = $this->binds[$info[0]];
        } else {
            $class = '\\funsent\\console\\command\\' . strtolower($info[0]) . '\\' . ucfirst($info[0]);
        }
        $action = isset($info[1]) ? $info[1] : 'handle';
        if (class_exists($class)) {
            return call_user_func_array([new $class(), strtolower($action)], $_SERVER['argv']);
        } else {
            return $this->error('command does not exist');
        }
    }

    /**
     * 以脚本方式执行命令
     * @param string $cli 命令
     * @return mixed
     */
    public function call($cli)
    {
        $_SERVER['argv'] = preg_split('/\s+/', $cli);
        return $this->run();
    }

    /**
     * 绑定命令
     * @param string $name
     * @param \Closure $callback
     * @return void
     */
    public function bind($name, Closure $callback)
    {
        $this->binds[$name] = $callback;
    }

    /**
     * 批量绑定命令
     * @param array $binds
     * @return void
     */
    public function setBinds($binds)
    {
        $this->binds = array_merge($this->binds, $binds);
    }

    /**
     * 发送异常到
     * @param \funsent\error\exception\Exception $e
     * @return void
     */
    public function renderException(Exception $e)
    {
        die($e->getMessage());
    }
}
