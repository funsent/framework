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
 * Simple模板引擎
 * @package funsent\view\driver\simple
 */
class Simple
{
    use SimpleCompile, SimpleCache;

    /**
     * 模板变量
     * @var array
     */
    protected $vars             = [];

    /**
     * 模板目录
     * @var string
     */
    protected $templatePath     = __DIR__ . '/template';

    /**
     * 模板文件名，带后缀
     * @var string
     */
    protected $templateFile     = '';

    /**
     * 调试开启时不缓存模板
     * @var boolean
     */
    protected $debug            = false;

    /**
     * 设置模板路径
     * @param string $templatePath
     * @return $this
     */
    public function setTemplatePath($templatePath = __DIR__ . '/template')
    {
        $this->templatePath = $templatePath;
        return $this;
    }

    /**
     * 设置模板文件名
     * @param string $templateFile
     * @return $this
     */
    public function setTemplateFile($templateFile = '')
    {
        $this->templateFile = $templateFile;
        return $this;
    }

    /**
     * 设置调试
     * @param boolean $debug
     * @return $this
     */
    public function setDebug($debug = false)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * 分配模板变量
     * @param array|string $name
     * @param array|string $value
     * @return $this
     */
    public function assign($name, $value = '')
    {
        if (is_string($name)) {
            $this->vars[$name] = $value;
        } elseif (is_array($name)) {
            $this->vars = array_merge($this->vars, $name);
        }
        return $this;
    }

    /**
     * 清除模板变量
     * @param array|string $name
     * @return $this
     */
    public function clearAssign($name)
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
     * 清除所有模板变量
     * @return $this
     */
    public function clearAllAssign()
    {
        $this->vars = [];
        return $this;
    }

    /**
     * 获取模板变量
     * @param string $name
     * @return array|mixed|null
     */
    public function getTemplateVars($name = '')
    {
        if (empty($name)) {
            return $this->vars;
        }
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    /**
     * 获取模板编译后的内容
     * 自动处理编译和缓存
     * @param string $template
     * @param string $cacheId
     * @param string $compileId
     * @return string
     */
    public function fetch($template = '', $cacheId = '', $compileId = '')
    {
        // 检测模板是否存在
        $this->checkTemplateFile($template, $cacheId, $compileId);

        // 是否在使用缓存
        $useCache = $this->useCache();

        // 检测缓存
        if ($useCache && ($cache = $this->getCache($template, $cacheId))) {
            return $cache;
        }

        // 编译解析模板
        $file = $this->compile($template, $compileId);

        // 获取编译内容
        ob_start();
        extract($this->vars);
        include $file;
        $content = ob_get_clean();

        // 创建缓存
        if ($useCache) {
            $this->setCache($content, $template, $cacheId);
        }
        return $content;
    }

    /**
     * 显示编译后的模板内容
     * @param string $template
     * @param string $cacheId
     * @param string $compileId
     * @return string
     */
    public function display($template = '', $cacheId = '', $compileId = '')
    {
        ob_start();
        ob_implicit_flush(0);
        echo $this->fetch($template, $cacheId, $compileId);
        ob_end_flush();
    }

    /**
     * 检测模板文件是否存在
     * @param string $template
     * @param string $cacheId
     * @param string $compileId
     * @return boolean
     */
    protected function checkTemplateFile($template = '', $cacheId = '', $compileId = '')
    {
        $templateFile = $this->getTemplateFile($template);
        if (!is_file($this->templatePath . '/' . $templateFile)) {
            $this->deleteCache($templateFile, $cacheId);
            $this->deleteCompile($templateFile, $compileId);
            trigger_error('Unable to load template ' . $templateFile, E_USER_ERROR);
        }
        return true;
    }

    /**
     * 检测模板文件名
     * @param string $template
     * @return string
     */
    public function getTemplateFile($template = '')
    {
        return empty($template) ? $this->templateFile : $template;
    }
}
