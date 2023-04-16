<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\kernel\view\build;

use funsent\kernel\config\Config;
use funsent\kernel\cache\Cache as ViewCache;

/**
 * 视图缓存trait
 * @package funsent\kernel\view\org
 */
trait Cache
{
    /**
     * 视图缓存时间，-1表示关闭缓存，0表示永久缓存
     * @var integer
     */
    protected $lifetime = -1;

    /**
     * 存取视图缓存时间
     * @param integer $lifetime 时间
     * @return $this|integer
     */
    public function lifetime($lifetime = null)
    {
        // 获取视图缓存时间
        if (is_null($lifetime)) {
            return $this->lifetime;
        }

        // 设置视图缓存时间
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * 设置或获取视图缓存
     * @param string|null $content
     * @return mixed
     */
    public function cache($content = null)
    {
        $dir = '/view/' . Config::get('view.cache_dir');

        // 获取视图缓存
        if (is_null($content)) {
            if ($this->lifetime < 0) {
                return null;
            }
            $cache = ViewCache::driver('file')->dir($dir);
            return $cache->get($this->cacheIdentifier());
        }

        // 设置视图缓存
        if ($this->lifetime >= 0) {
            $cache = ViewCache::driver('file')->dir($dir);
            $cache->set($this->cacheIdentifier(), $content, $this->lifetime);
        }
        
        return $this;
    }

    /**
     * 生成视图缓存标识
     * @param string|null $file
     * @return string
     */
    protected function cacheIdentifier($file = null)
    {
        $file = is_null($file) ? $this->file() : $file;
        return md5($_SERVER['REQUEST_URI'] . $file);
    }

    /**
     * 检查视图是否被缓存
     * @return mixed
     */
    public function isCache()
    {
        return ViewCache::driver('file')
                ->dir('/view/' . Config::get('view.cache_dir'))
                ->has($this->cacheIdentifier());
    }

    /**
     * 删除视图缓存
     * @param string|null $file
     * @return mixed
     */
    public function deleteCache($file = null)
    {
        return ViewCache::driver('file')
                ->dir('/view/' . Config::get('view.cache_dir'))
                ->delete($this->cacheIdentifier($file));
    }
}
