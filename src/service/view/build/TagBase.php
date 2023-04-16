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

/**
 * 标签处理抽象类
 * @package funsent\kernel\view\org
 */
abstract class TagBase
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
     * @return string
     */
    public function parse()
    {
        // 设置分界符
        $this->leftDelimiter = Config::get('view.left_delimiter');
        $this->rightDelimiter = Config::get('view.right_delimiter');

        // 解析标签
        foreach ($this->tags as $tag => $param) {
            if ($param['block']) {
                // 解析块标签
                $this->parseBlock($tag, $param);
            } else {
                // 解析行标签
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
