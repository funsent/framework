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
 * Simple模板编译处理 trait
 * @package funsent\view\driver\simple
 */
trait SimpleCompile
{
    /**
     * 标签左分界符
     * @var string
     */
    protected $leftDelimiter    = '{{';

    /**
     * 标签右分界符
     * @var string
     */
    protected $rightDelimiter   = '}}';

    /**
     * 扩展标签
     * @var array
     */
    protected $tags             = [];

    /**
     * 内置标签
     * @var array
     */
    protected $simpleTags       = ['\\funsent\\view\\src\\simple\\SimpleTags'];

    /**
     * 编译路径
     * @var string
     */
    protected $compilePath      = __DIR__ . '/template/compile';

    /**
     * 表单CSRF令牌字段
     * @var boolean
     */
    protected $formCsrf         = true;

    /**
     * 设置标签左分界符
     * @param string $leftDelimiter
     * @return $this
     */
    public function setLeftDelimiter($leftDelimiter = '{{')
    {
        $this->leftDelimiter = $leftDelimiter;
        return $this;
    }

    /**
     * 设置标签右分界符
     * @param string $rightDelimiter
     * @return $this
     */
    public function setRightDelimiter($rightDelimiter = '}}')
    {
        $this->rightDelimiter = $rightDelimiter;
        return $this;
    }

    /**
     * 设置编译路径
     * @param string $compilePath
     * @return $this
     */
    public function setCompilePath($compilePath = __DIR__ . '/template/compile')
    {
        $this->compilePath = $compilePath;
        return $this;
    }

    /**
     * 设置扩展标签
     * @param array|string $tags
     * @return $this
     */
    public function setTags($tags = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    /**
     * 设置表单CSRF令牌字段
     * @param boolean $formCsrf
     * @return $this
     */
    public function setFormCsrf($formCsrf = true)
    {
        $this->formCsrf = $formCsrf;
        return $this;
    }

    /**
     * 获取编译文件名
     * @param string $template
     * @param string $compileId
     * @return string
     */
    public function getCompileFile($template = '', $compileId = '')
    {
        return (empty($compileId) ? md5($this->getTemplateFile($template)) : $compileId) . '.php';
    }

    /**
     * 删除编译
     * @param string $template
     * @param string $compileId
     * @return $this
     */
    public function deleteCompile($template = '', $compileId = '')
    {
        $compileFile = $this->getCompileFile($template, $compileId);
        $file = $this->compilePath . '/' . $compileFile;
        if (is_file($file)) {
            unlink($file);
        }
        return $this;
    }

    /**
     * 编译解析模板
     * @param string $template
     * @param string $compileId
     * @return string
     */
    public function compile($template = '', $compileId = '')
    {
        $compileFile = $this->getCompileFile($template, $compileId);
        $file = $this->compilePath . '/' . $compileFile;
        $templateFile = $this->templatePath . '/' . $this->getTemplateFile($template);
        if ($this->debug || !is_file($file) || (filemtime($templateFile) > filemtime($file))) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            $content = file_get_contents($templateFile);
            $content = $this->parseTags($content);
            $content = $this->parseVars($content);
            $content = $this->parseCsrf($content);
            file_put_contents($file, $content);
        }
        return $file;
    }

    /**
     * 解析标签
     * @param string $content
     * @return string
     */
    protected function parseTags($content)
    {
        $tags = array_merge($this->tags, $this->simpleTags);
        foreach ($tags as $tag) {
            $content = (new $tag($content, $this))->parse($this->leftDelimiter, $this->rightDelimiter);
        }
        return $content;
    }

    /**
     * 解析变量，包括常量
     * @param string $content
     * @return string
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
     * @return string
     */
    protected function parseCsrf($content)
    {
        if ($this->formCsrf) {
            $content = preg_replace('#(<form.*>)#', '$1<?php echo csrf_field();?>', $content);
        }
        return $content;
    }
}