<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\kernel\view\build;

use funsent\kernel\application\App;
use funsent\kernel\config\Config;

/**
 * 视图编译trait
 * @package funsent\kernel\view\org
 */
trait Compile
{
    /**
     * 视图编译文件
     * @var string
     */
    protected $compileFile;

    /**
     * 视图文件内容
     * @var string
     */
    protected $content;

    /**
     * 设置视图编译文件
     * @return string
     */
    final public function setCompileFile()
    {
        $root = App::runtimePath() . '/view';
        $path = $root . '/' . Config::get('view.compile_dir');
        $this->compileFile = $path . '/' . md5($this->file) . '.php';
        // $this->compileFile = $path . '/' . preg_replace('/[^\w]/', '_', $this->file) . '_' . substr(md5($this->file), 0, 5) . '.php';
        return $this->compileFile;
    }

    /**
     * 获取视图文件内容
     * @return string
     */
    final public function getCompileContent()
    {
        return file_get_contents($this->compileFile);
    }

    /**
     * 创建视图编译文件
     * @return $this
     */
    final public function compile()
    {
        $this->setCompileFile();

        // 检测视图文件是否满足编译条件
        $status = Config::get('app.debug') || !is_file($this->compileFile) || (filemtime($this->file) > filemtime($this->compileFile));
        if ($status) {
            if (!is_dir(dirname($this->compileFile))) {
                mkdir(dirname($this->compileFile), 0755, true);
            }

            // 获取视图文件内容
            $this->content = file_get_contents($this->file);

            // 解析视图中的标签
            $this->parseTags();

            // 解析视图中的变量与常量
            $this->parseGlobalVarsAndConstants();

            // 为视图中的表单添加CSRF令牌
            $this->csrf();

            // 将视图文件内容写入编译文件
            file_put_contents($this->compileFile, $this->content);
        }

        return $this;
    }

    /**
     * 解析视图中的标签
     * @return void
     */
    final protected function parseTags()
    {
        // 加载标签库
        $tags = Config::get('view.tags');
        $tags[] = 'funsent\kernel\view\build\Tag';

        // 解析标签
        foreach ($tags as $class) {
            $instance = new $class($this->content, $this);
            $this->content = $instance->parse();
        }
    }

    /**
     * 解析视图中的变量与常量
     * @return void
     */
    final protected function parseGlobalVarsAndConstants()
    {
        // 解析{{}}
        $this->content = preg_replace('/(?<!@)\{\{(.*?)\}\}/i', '<?php echo \1?>', $this->content);

        // 解析@{{}}
        $this->content = preg_replace('/@(\{\{.*?\}\})/i', '\1', $this->content);
    }

    /**
     * 为视图中的表单添加CSRF令牌
     * @return void
     */
    final protected function csrf()
    {
        if (Config::get('csrf.open')) {
            $this->content = preg_replace('#(<form.*>)#', '$1' . PHP_EOL . '<?php echo csrf_field();?>', $this->content);
        }
    }
}
