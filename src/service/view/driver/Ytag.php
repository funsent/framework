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
 * Ytag模板引擎实现的视图服务
 * 
 * @package funsent\view\driver
 */
class Ytag extends ViewAbstract implements ViewInterface
{
    /**
     * 解析视图
     * 自动处理编译和缓存
     * @param string $file
     * @return string
     */
    public function parse($file = null)
    {
        // 检测视图
        $this->check($file);

        // 检测缓存
        if (!$this->config['debug'] && $this->config['cache_lifetime'] >= 0 && ($cache = $this->getCache($file))) {
            return $cache;
        }

        // 编译视图
        $compileFile = $this->compile($file);

        // 获取编译内容
        ob_start();
        extract($this->vars);
        include $compileFile;
        $content = ob_get_clean();

        // 创建缓存
        if (!$this->config['debug'] && $this->config['cache_lifetime'] >= 0) {
            $this->setCache($content);
        }
        return $content;
    }

    /**
     * 检测视图文件是否存在
     * @param string|null $file
     * @return boolean
     */
    public function check($file = null)
    {
        $viewFile = $this->getViewFile($file);
        if (!is_file($viewFile)) {
            $this->deleteCompile($file);
            $this->deleteCache($file);
            trigger_error('View file not found: ' . $viewFile, E_USER_ERROR);
        }
        return true;
    }

    /**
     * 获取视图文件全路径文件名
     * @param string|null $file
     * @return string
     */
    public function getViewFile($file = null)
    {
        $filename = is_null($file) ? $this->config['file'] : $file;
        return $this->config['view_path'] . '/' . $filename;
    }

    /**
     * 获取编译文件全路径文件名
     * @param string|null $file
     * @return string
     */
    public function getCompileFile($file = null)
    {
        return $this->config['compile_path'] . '/' . md5($this->getViewFile($file)) . '.php';
    }

    /**
     * 删除编译
     * @param string|null $file
     * @return booean
     */
    public function deleteCompile($file = null)
    {
        return unlink($this->getCompileFile($file));
    }

    /**
     * 编译
     * @param string|null $file
     * @return string 返回编译文件全路径文件名
     */
    public function compile($file = null)
    {
        $compileFile = $this->getCompileFile($file);
        $viewFile = $this->getViewFile($file);
        if ($this->config['debug'] || !is_file($compileFile) || (filemtime($viewFile) > filemtime($compileFile))) {
            if (!is_dir(dirname($compileFile))) {
                mkdir(dirname($compileFile), 0755, true);
            }
            $content = file_get_contents($viewFile);
            $content = $this->parseTags($content);
            $content = $this->parseVars($content);
            $content = $this->parseCsrf($content);
            file_put_contents($compileFile, $content);
        }
        return $compileFile;
    }

    /**
     * 解析标签
     * @param string $content
     * @return void
     */
    protected function parseTags($content)
    {
        $tags = array_merge($this->config['tags'], ['funsent\kernel\view\src\ytag\Ytag']);
        foreach ($tags as $tag) {
            $content = (new $tag($content, $this))->parse();
        }
        return $content;
    }

    /**
     * 解析变量，包括常量
     * @param string $content
     * @return void
     */
    protected function parseVars($content)
    {
        $content = preg_replace('/(?<!@)\{\{(.*?)\}\}/i', '<?php echo \1;?>', $content); // 解析{{}}
        $content = preg_replace('/@(\{\{.*?\}\})/i', '\1', $content); // 解析@{{}}
        return $content;
    }

    /**
     * 解析CSRF令牌，为form添加CSRF令牌字段
     * @param string $content
     * @return void
     */
    protected function parseCsrf($content)
    {
        if ($this->config['csrf']) {
            $content = preg_replace('#(<form.*>)#', '$1<?php echo csrf_field();?>', $content);
        }
        return $content;
    }

    /**
     * 获取缓存文件全路径文件名
     * @param string|null $file
     * @return string
     */
    public function getCacheFile($file = null)
    {
        return $this->config['cache_path'] . '/' . md5($this->getViewFile($file)) . '.php';
    }

    /**
     * 检测缓存
     * @param string|null $file
     * @return boolean
     */
    public function isCached($file = null)
    {
        // 读取数据
        $cacheFile = $this->getCacheFile($file);
        if (!is_file($cacheFile)) {
            return false;
        }
        $cacheContent = file_get_contents($cacheFile);
        if (false === $cacheContent) {
            return false;
        }

        // 检测数据过期
        $cacheLifetime = (int)substr($cacheContent, 4, 12);
        if ($cacheLifetime != 0 && (filemtime($cacheFile) + $cacheLifetime < time())) {
            unlink($cacheFile);
            return false;
        }

        // 数据校验
        if ($this->config['cache_check']) {
            $check = substr($cacheContent, 16, 32);
            $value = substr($cacheContent, 51);
            if ($check != md5($value)) {
                unlink($cacheFile);
                return false;
            }
        }
        return true;
    }

    /**
     * 获取缓存
     * @param string|null $file
     * @return string
     */
    public function getCache($file = null)
    {
        // 读取数据
        $cacheFile = $this->getCacheFile($file);
        if (!is_file($cacheFile)) {
            return null;
        }
        $cacheContent = file_get_contents($cacheFile);
        if (false === $cacheContent) {
            return null;
        }

        // 检测数据过期
        $cacheLifetime = (int)substr($cacheContent, 4, 12);
        if ($cacheLifetime != 0 && (filemtime($cacheFile) + $cacheLifetime < time())) {
            unlink($cacheFile);
            return null;
        }

        // 数据校验
        if ($this->config['cache_check']) {
            $check = substr($cacheContent, 16, 32);
            $value = substr($cacheContent, 51);
            if ($check != md5($value)) {
                unlink($cacheFile);
                return null;
            }
        } else {
            $value = substr($cacheContent, 19);
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param string|null $file
     * @return $this|boolean
     */
    public function setCache($content, $file = null)
    {
        $cacheFile = $this->getCacheFile($file);
        $check = $this->config['cache_check'] ? md5($content) : '';
        $content = '<!--' . sprintf('%012d', $this->config['cache_lifetime']) . $check . '-->' . $content;
        if (file_put_contents($cacheFile, $content)) {
            clearstatcache();
        }
        return $this;
    }

    /**
     * 删除缓存
     * @param string|null $file
     * @return void
     */
    public function deleteCache($file = null)
    {
        $cacheFile = $this->getCacheFile($file);
        if (is_file($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
