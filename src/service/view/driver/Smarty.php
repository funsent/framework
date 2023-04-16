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

namespace funsent\service\view;

use funsent\view\contract\ViewAbstract;
use funsent\view\contract\ViewInterface;

/**
 * Smarty模板引擎实现的视图服务
 * 
 * @package funsent\view\driver
 */
class Smarty extends ViewAbstract implements ViewInterface
{
    /**
     * 获取模板引擎实例
     * 
     * @param array $option
     * @return \Smarty
     */
    public static function instance(array $option)
    {
        static $instance = null;
        if (is_null($instance)) {
            // 创建实例
            require_once __DIR__ . '/smarty/libs/Smarty.class.php';
            $instance = new \Smarty();
            $instance->setLeftDelimiter($option['left_delimiter']);
            $instance->setRightDelimiter($option['right_delimiter']);
            $instance->setTemplateDir($option['view_path']);
            $instance->setCompileDir($option['compile_path']);

            // 加快文件系统的搜索速度，Windows上会引起一些问题
            $useSub = (false !== strpos(PHP_OS, 'WIN')) ? false : true;
            $instance->setUseSubDirs($useSub);

            // 关闭调试功能
            $instance->setDebugging(false);

            // 提高包含了许多子视图的视图文件的渲染速度
            $instance->setMergeCompiledIncludes(true);

            // 为所有变量调用 htmlspecialchars
            // $instance->setEscapeHtml(true);

            // 编译和缓存设置
            if ($option['debug']) {
                $instance->setCompileCheck(true);
                $instance->setForceCompile(true);
            } else {
                $instance->setCompileCheck(false);
                $instance->setForceCompile(false);

                // -1为缓存永不过期
                $lifetime = $option['cache_lifetime'] == 0 ? -1 : $option['cache_lifetime'];
                $instance->setCaching(\Smarty::CACHING_LIFETIME_SAVED);
                $instance->setCacheLifetime($lifetime);
            }
        }

        // 设置模板文件路径
        $instance->setTemplateDir($option['view_path']);

        // 设置模板编译路径
        $instance->setCompileDir($option['compile_path']);

        // 设置模板缓存路径
        $instance->setCacheDir($option['cache_path']);

        return $instance;
    }

    /**
     * 解析视图
     * 自动处理编译和缓存
     * @param string|null $file
     * @return string
     */
    public function parse($file = null)
    {
        return $this->fetch($file);
    }

    /**
     * 获取视图文件解析后的内容
     * @param string|null $file
     * @return string
     */
    public function fetch($file = null)
    {
        $file = is_null($file) ? $option['file'] : $file;
        if (!is_file($option['view_path'] . '/' . $file)) {
            trigger_error('View file not found: ' . $file, E_USER_ERROR);
        }
        return $this->engine()->fetch($file);
    }

    /**
     * 检测缓存
     * @param string $file
     * @param string $cacheId
     * @param string $compileId
     * @return boolean
     */
    public function isCached($file = null, $cacheId = null, $compileId = null)
    {
        return $option['debug'] ? false : $this->engine()->isCached($file, $cacheId, $compileId);
    }
}
