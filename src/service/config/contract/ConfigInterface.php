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

namespace funsent\service\contract;

/**
 * 配置 接口
 */
interface ConfigInterface
{
    /**
     * 检测配置是否存在
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 读取配置
     *
     * @param string $key 支持通过.号获取多级配置
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null): mixed;

    /**
     * 读取所有配置
     *
     * @return array
     */
    public function all(): array;

    /**
     * 设置配置，支持通过数组参数批量设置
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value = null): void;
}
