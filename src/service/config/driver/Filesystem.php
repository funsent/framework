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

namespace funsent\service\driver;

use funsent\service\contract\ConfigInterface;

/**
 * 基于文件系统的配置实现
 */
class Filesystem implements ConfigInterface
{
    /**
     * 数据
     *
     * @var array
     */
    protected static $data = [];

    /**
     * 加载指定目录下的配置定义文件
     *
     * @param string $dir
     * @return void
     */
    public function load(string $dir = ''): void
    {
        $dir = empty($dir) ? $this->app->getRootPath() . 'config' . DIRECTORY_SEPARATOR : $dir;
        foreach (glob($dir . '/*') as $file) {
            $info = pathinfo($file, PATHINFO_ALL);
            $this->set($info['filename'], include $file);
        }
    }

    /**
     * 检测配置是否存在
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $data = self::$data;
        $keys = (array)explode('.', $key);
        foreach ($keys as $v) {
            if (isset($data[$v])) {
                $data = $data[$v];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 读取配置
     *
     * @param string $key 支持通过.号获取多级配置
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        $data = self::$data;
        $keys = (array)explode('.', $key);
        foreach ($keys as $d) {
            if (isset($data[$d])) {
                $data = $data[$d];
            } else {
                return $default;
            }
        }
        return $data;
    }

    /**
     * 读取所有配置
     *
     * @return array
     */
    public function all(): array
    {
        return self::$data;
    }

    /**
     * 设置配置，支持通过数组参数批量设置
     *
     * @param string|array $key 支持通过字符串参数中的.号设置多级配置
     * @param mixed $value
     * @return void
     */
    public function set($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, is_array($k) ? null : $v);
            }
        } else {
            $data = &self::$data;
            $keys = (array)explode('.', $key);
            foreach ($keys as $v) {
                if (!isset($data[$v])) {
                    $data[$v] = [];
                }
                $data = &$data[$v];
            }
            $data = $value;
        }
    }
}
