<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\kernel\view\build;

use funsent\kernel\arr\Arr;
use funsent\kernel\config\Config;
use funsent\kernel\application\App;
use funsent\kernel\middleware\Middleware;

/**
 * 视图服务实现
 * @package funsent\kernel\view\build
 */
class Base
{
    use Compile, Cache;

    /**
     * 视图变量
     * @var array
     */
    protected static $vars = [];

    /**
     * 视图全路径文件名
     * @var string
     */
    protected $file;

    /**
     * 视图文件存放路径
     * @var string
     */
    protected $path;

    /**
     * 获取视图实例
     * @return funsent\kernel\view\build\Base
     */
    public function instance()
    {
        return new self();
    }

    /**
     * 创建视图对象
     * @param string $file 视图文件
     * @param mixed $vars 视图变量
     * @return $this
     */
    public function make($file = '', $vars = [])
    {
        $this->file($file);
        $this->with($vars);
        Middleware::web('view_parse_file');
        return $this;
    }

    /**
     * 分配视图变量
     * @param array|string $vars 视图变量
     * @param string $value 变量
     * @return $this
     */
    public function with($vars, $value = '')
    {
        self::vars($vars, $value);
        return $this;
    }

    /**
     * 设置或获取视图变量
     * @param null|array|string $vars 视图变量
     * @param mixed $value 变量值
     * @return string|array|null
     */
    public static function vars($vars = null, $value = null)
    {
        if (is_null($vars)) {
            // 获取所有视图变量
            return self::$vars;
        } elseif (is_string($vars) && is_null($value)) {
            // 获取单个视图变量
            return isset(self::$vars[$vars]) ? self::$vars[$vars] : null;
        } else {
            // 设置视图变量
            $vars = is_array($vars) ? $vars : [$vars => $value];
            foreach ($vars as $key => $value) {
                self::$vars = Arr::set(self::$vars, $key, $value);
            }
        }
    }

    /**
     * 获取视图解析后的内容
     * @param string $file 视图文件
     * @param mixed $vars 视图变量
     * @return string
     */
    public function fetch($file = '', $vars = [])
    {
        return $this->make($file, $vars)->parse();
    }

    /**
     * 设置或获取视图目录
     * @param array $path
     * @return $this|string
     */
    public function path($path = null)
    {
        if (is_null($path)) {
            return $this->path;
        }
        $this->path = $path;
        return $this;
    }

    /**
     * 设置或获取视图文件
     * @param string $file 视图文件
     * @return $this|string
     */
    public function file($file = null)
    {
        // 获取视图文件
        if (is_null($file)) {
            return $this->file;
        }

        // 设置视图文件
        if ($file && !preg_match('/\.[a-z]+$/i', $file)) {
            $file .= Config::get('view.suffix');
        }
        if (strstr($file, '/') && is_file($file)) {
            $this->file = $file;
        } else {
            $file = realpath(App::path() . '/../') . '/' . $this->path . '/' . $file;
            if (is_file($file)) {
                $this->file = $file;
            }
        }
        return $this;
    }

    /**
     * 解析视图
     * @return string
     */
    protected function parse()
    {
        if (!is_file($this->file)) {
            trigger_error('View not found: ' . $this->file, E_USER_ERROR);
        }
        $this->compile();
        ob_start();
        extract(self::vars());
        include $this->compileFile;
        return ob_get_clean();
    }

    /**
     * 显示视图
     * @return string
     */
    public function toString()
    {
        if ($cache = $this->cache()) {
            return $cache;
        }
        $content = $this->parse();
        $this->cache($content);
        return $content;
    }

    /**
     * 显示视图
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
