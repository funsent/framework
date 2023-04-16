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

namespace funsent\view\driver\simple;

/**
 * Simple模板缓存处理 trait
 * @package funsent\view\driver\simple
 */
trait SimpleCache
{
    /**
     * 缓存文件路径
     * @var string
     */
    protected $cachePath        = __DIR__ . '/template/cache';

    /**
     * 缓存有效时间，0表示不缓存，-1表示永久缓存
     * @var integer >=0|-1
     */
    protected $cacheLifetime    = 0;

    /**
     * 缓存校验
     * @var boolean
     */
    protected $cacheCheck       = true;

    /**
     * 设置缓存路径
     * @param string $cachePath
     * @return $this
     */
    public function setCachePath($cachePath = __DIR__ . '/template/cache')
    {
        $this->cachePath = $cachePath;
        return $this;
    }

    /**
     * 设置缓存时间
     * @param integer $cacheLifetime
     * @return $this
     */
    public function setCacheLifetime($cacheLifetime = 0)
    {
        $this->cacheLifetime = $cacheLifetime;
        return $this;
    }

    /**
     * 设置缓存校验
     * @param boolean $cacheCheck
     * @return $this
     */
    public function setCacheCheck($cacheCheck = true)
    {
        $this->cacheCheck = $cacheCheck;
        return $this;
    }

    /**
     * 是否在使用缓存
     * @return boolean
     */
    public function useCache()
    {
        return (!$this->debug && $this->cacheLifetime) ? true : false;
    }

    /**
     * 获取缓存文件名
     * @param string $template
     * @param string $cacheId
     * @return string
     */
    public function getCacheFile($template = '', $cacheId = '')
    {
        return (empty($cacheId) ? md5($this->getTemplateFile($template)) : $cacheId) . '.php';
    }

    /**
     * 检测缓存
     * @param string $template
     * @param string $cacheId
     * @return boolean
     */
    public function isCached($template = '', $cacheId = '')
    {
        // 读取缓存内容
        $cacheFile = $this->getCacheFile($template, $cacheId);
        $file = $this->cachePath . '/' . $cacheFile;
        if (!is_file($file) || (false === ($content = file_get_contents($file)))) {
            return false;
        }

        // 检测缓存过期
        $cacheLifetime = (int)substr($content, 4, 12);
        if ($cacheLifetime != -1 && (filemtime($file) + $cacheLifetime < time())) {
            unlink($file);
            return false;
        }

        // 缓存校验
        if ($this->cacheCheck) {
            $check = substr($content, 16, 32);
            $value = substr($content, 51);
            if ($check != md5($value)) {
                unlink($file);
                return false;
            }
        }
        return true;
    }

    /**
     * 获取缓存
     * @param string $template
     * @param string $cacheId
     * @return string
     */
    public function getCache($template = '', $cacheId = '')
    {
        // 读取缓存内容
        $cacheFile = $this->getCacheFile($template, $cacheId);
        $file = $this->cachePath . '/' . $cacheFile;
        if (!is_file($file) || (false === ($content = file_get_contents($file)))) {
            return null;
        }

        // 检测缓存过期
        $cacheLifetime = (int)substr($content, 4, 12);
        if ($cacheLifetime != -1 && (filemtime($file) + $cacheLifetime < time())) {
            unlink($file);
            return null;
        }

        // 缓存校验
        if ($this->cacheCheck) {
            $check = substr($content, 16, 32);
            $value = substr($content, 51);
            if ($check != md5($value)) {
                unlink($file);
                return null;
            }
        } else {
            $value = substr($content, 19);
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param string $content
     * @param string $template
     * @param string $cacheId
     * @return $this
     */
    public function setCache($content, $template = '', $cacheId = '')
    {
        $cacheFile = $this->getCacheFile($template, $cacheId);
        $file = $this->cachePath . '/' . $cacheFile;
        $check = $this->cacheCheck ? md5($content) : '';
        $content = '<!--' . sprintf('%012d', $this->cacheLifetime) . $check . '-->' . $content;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        if (file_put_contents($file, $content)) {
            clearstatcache();
        }
        return $this;
    }

    /**
     * 删除缓存
     * @param string $template
     * @param string $cacheId
     * @return $this
     */
    public function deleteCache($template = '', $cacheId = '')
    {
        $cacheFile = $this->getCacheFile($template, $cacheId);
        $file = $this->cachePath . '/' . $cacheFile;
        if (is_file($file)) {
            unlink($file);
        }
        return $this;
    }
}