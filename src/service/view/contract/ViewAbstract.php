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

namespace funsent\view\contract;

use funsent\app\App;

/**
 * 视图服务基类
 * 
 * @package funsent\view\contract
 */
abstract class ViewAbstract
{
    /**
     * 配置
     * @var array
     */
    protected $config = [
        'debug'                 => false,
        'tags'                  => [],
        'tag_left_delimiter'    => '<',
        'tag_right_delimiter'   => '>',
        'view_dir'              => 'view',
        'view_suffix'           => '.php',
        //'file'             => '',
        'compile_dir'           => 'compile',
        'cache_dir'             => 'cache',
        'cache_lifetime'        => -1,
        'cache_check'           => true, // 缓存校验
    ];

    /**
     * 变量
     * @var array
     */
    protected $vars = [];

    /**
     * 构造方法
     * @param array $config
     */
    final public function __construct($config)
    {
        $module = Route::module();
        $controller = Route::controller();
        $action = Route::action();
        if ($module) {
            $viewPath = App::path() . '/' . $module . '/' . trim($config['view_dir'], '\\/') . '/' . $controller;
        } else {
            $viewPath = App::path() . '/' . trim($config['view_dir'], '\\/');
        }
        $viewPath       = MODULE_PATH . '/' . trim($config['view_dir'], '\\/') . '/' . CONTROLLER_NAME;
        $compilePath    = App::runtimePath() . '/view/' . trim($config['compile_dir'], '\\/') . '/' . CONTROLLER_NAME;
        $cachePath      = App::runtimePath() . '/view/' . trim($config['cache_dir'], '\\/') . '/' . CONTROLLER_NAME;
        $this->config   = array_merge($config, [
            'view_path'     => $viewPath,
            'compile_path'  => $compilePath,
            'cache_path'    => $cachePath,
        ]);
    }

    /**
     * 设置视图文件
     * @param string $file 视图文件
     * @return $this|string
     */
    public function file($file = '')
    {
        if (!preg_match('/\.[a-z]+$/i', $file)) {
            $file .= $this->config['view_suffix'];
        }
        $this->config['file'] = $file;
        return $this;
    }

    /**
     * 创建实例
     * @param string $file
     * @param array $vars
     * @return $this
     */
    public function make($file = '', $vars = [])
    {
        $this->file($file);
        $this->with($vars);
        return $this;
    }

    /**
     * 获取视图解析后的内容
     * @param string $file
     * @param mixed $vars
     * @return string
     */
    public function fetch($file = '', $vars = [])
    {
        return $this->make($file, $vars)->parse();
    }

    /**
     * 分配视图变量
     * @param array|string $name 数组时为批量设置
     * @param mixed $value
     * @return $this
     */
    public function with($name, $value = '')
    {
        if (is_array($name)) {
            $this->batch($name);
        } else {
            $this->set($name, $value);
        }
        return $this;
    }

    /**
     * 设置变量
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * 批量设置变量
     * @param array $vars
     * @return $this
     */
    public function batch(array $vars)
    {
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    /**
     * 获取变量
     * @param string $name
     * @return mixed|null
     */
    public function get($name)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    /**
     * 获取所有变量
     * @return array
     */
    public function all()
    {
        return $this->vars;
    }

    /**
     * 删除变量
     * @param string|array $name
     * @return $this
     */
    public function delete($name)
    {
        if (is_string($name)) {
            $name = explode(',', str_replace(' ', '', $name));
        }
        if (is_array($name)) {
            $this->vars = array_diff_key($this->vars, $name);
        }
        return $this;
    }

    /**
     * 清除变量
     * @return $this
     */
    public function flush()
    {
        $this->vars = [];
        return $this;
    }

    /**
     * 渲染视图
     * @return string
     */
    public function render()
    {
        return $this->parse();
    }

    /**
     * 显示视图
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
