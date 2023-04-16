<?php

/**
 * funsent - A PHP Framework For Web Application
 *
 * @link      http://www.funsent.com/
 * @copyright 2020 funsent.com, Inc.
 * @author    yanggf <2018708@qq.com>
 * @package   funsent
 * @version   1.1.2
 */

namespace funsent\view;

use funsent\kernel\view\build\Base;

/**
 * 视图服务
 * @package funsent\kernel\view
 */
class View
{
    /**
     * 实现类实例
     * @var funsent\kernel\view\build\Base;
     */
    protected static $instance;

    /**
     * 创建单例对象
     * @return funsent\kernel\view\build\Base
     */
    public static function singleton()
    {
        if (!self::$instance) {
            self::$instance = new Base();
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
        return call_user_func_array([self::singleton(), $method], $parameters);
    }
}
