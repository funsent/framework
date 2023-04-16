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
 * Simple模板标签处理抽象类
 * @package funsent\view\driver\simple
 */
abstract class SimpleTagsAbstract
{
    /**
     * 视图对象
     * @var object
     */
    protected $view;

    /**
     * 视图文件内容
     * @var string
     */
    protected $content;

    /**
     * 左分界符
     * @var string
     */
    protected $leftDelimiter;

    /**
     * 右分界符
     * @var string
     */
    protected $rightDelimiter;

    /**
     * 替换表达式
     * @var array
     */
    protected $exp = [
        '/\s+eq\s+/'  => '==',
        '/\s+neq\s+/' => '!=',
        '/\s+gt\s+/'  => '>',
        '/\s+lt\s+/'  => '<',
        '/\s+lte\s+/' => '<=',
        '/\s+gte\s+/' => '>=',
    ];

    /**
     * 构造函数
     * @param string $content 视图内容
     * @param object $view 视图对象
     */
    public function __construct($content, $view)
    {
        $this->content = $content;
        $this->view = $view;
    }

    /**
     * 解析标签
     * @param string $leftDelimiter
     * @param string $rightDelimiter
     * @return string
     */
    public function parse($leftDelimiter = '{{', $rightDelimiter = '}}')
    {
        // 设置分界符
        $this->leftDelimiter = $leftDelimiter;
        $this->rightDelimiter = $rightDelimiter;

        // 解析标签
        foreach ($this->tags as $tag => $param) {
            if ($param['block']) {
                $this->parseBlock($tag, $param);
            } else {
                $this->parseLine($tag, $param);
            }
        }

        // 返回解析后的内容
        return $this->content;
    }

    /**
     * 解析块标签
     * @param string $tag
     * @param array $param
     * @return void
     */
    private function parseBlock($tag, $param)
    {
        for ($i = 1; $i <= $param['level']; $i++) {
            $preg = '#' . $this->leftDelimiter . '(?:' . $tag . '|' . $tag . '\s+(.*?))' . $this->rightDelimiter . '(.*?)' . $this->leftDelimiter . '/' . $tag . $this->rightDelimiter . '#is';
            if (preg_match_all($preg, $this->content, $matchs, PREG_SET_ORDER)) {
                foreach ($matchs as $match) {
                    // 获取属性
                    $attr = empty($match[1]) ? [] : $this->getAttribute($match[1]);

                    // 执行标签方法
                    $method = '_' . $tag;
                    $replace = $this->$method($attr, $match[2], $this->view);

                    // 替换模板内容
                    $this->content = str_replace($match[0], $replace, $this->content);
                }
            } else {
                return;
            }
        }
    }

    /**
     * 解析行标签
     * @param string $tag
     * @return void
     */
    private function parseLine($tag)
    {
        $preg = '#' . $this->leftDelimiter . '(?:' . $tag . '|' . $tag . '\s+(.*?))\s*/?' . $this->rightDelimiter . '#is';
        if (preg_match_all($preg, $this->content, $matchs, PREG_SET_ORDER)) {
            foreach ($matchs as $match) {
                // 获取属性
                $attr = empty($match[1]) ? [] : $this->getAttribute($match[1]);

                // 执行标签方法
                $method = '_' . $tag;
                $replace = $this->$method($attr, '', $this->view);

                // 替换模板内容
                $this->content = str_replace($match[0], $replace, $this->content);
            }
        }
    }

    /**
     * 获取属性
     * @param string $subject
     * @return array
     */
    private function getAttribute($subject)
    {
        $attr = [];
        $preg = '#([^\=\s]+)\s*=\s*([\'"])(.*?)\2#i';
        if (preg_match_all($preg, $subject, $matches)) {
            foreach ($matches[1] as $i => $name) {
                // 替换表达式，如 eq neq 等
                $attr[$name] = preg_replace(array_keys($this->exp), array_values($this->exp), $matches[3][$i]);
            }
        }
        return $attr;
    }

    /**
     * 替换常量
     * @param $content 内容
     * @return mixed
     */
    protected function replaceConstants($content)
    {
        $constants = get_defined_constants(true);
        foreach ($constants['user'] as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        return $content;
    }
}
