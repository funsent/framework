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

namespace funsent\service\config;

use ArrayAccess;
use funsent\exception\MethodNotSupportedException;

/**
 * 环境变量
 * 
 * @package funsent
 */
class Env implements ArrayAccess
{
    /**
     * 数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->data = $_ENV;
    }

    /**
     * 加载环境变量定义文件
     *
     * @param string $file
     * @return $this
     */
    public function load(string $file = '')
    {
        $file = empty($file) ? $this->app->getRootPath() . '.env' : $file;
        if (is_file($file)) {
            $env = parse_ini_file($file, true) ?: [];
            $this->set($env);
        }

        return $this;
    }

    /**
     * 检测环境变量是否存在
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }

    /**
     * 读取环境变量
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        $key = strtoupper(str_replace('.', '_', $key));

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $this->getEnv($key, $default);
    }

    /**
     * 读取所有环境变量
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * 读取系统环境变量
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getEnv(string $key, $default = null): mixed
    {
        $value = getenv('PHP_' . $key);

        if (false === $value) {
            return $default;
        }

        if ('false' === $value) {
            $value = false;
        } elseif ('true' === $value) {
            $value = true;
        }

        if (!isset($this->data[$key])) {
            $this->data[$key] = $value;
        }

        return $value;
    }

    /**
     * 设置环境变量， 支持通过数组参数批量设置
     *
     * @param string|array $env
     * @param mixed $value
     * @return void
     */
    public function set($env, $value = null): void
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);
            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $this->data[$key . '_' . strtoupper($k)] = $v;
                    }
                } else {
                    $this->data[$key] = $val;
                }
            }
        } else {
            $key = strtoupper(str_replace('.', '_', $env));
            $this->data[$key] = $value;
        }
    }


    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }


    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        throw new MethodNotSupportedException(sprintf('method %s not support', 'unset'));
    }
}
