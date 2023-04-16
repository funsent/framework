<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\kernel\view\build;

use funsent\kernel\view\View;

/**
 * 标签处理类
 * @package funsent\kernel\view\org
 */
class Tag extends TagBase
{
    /**
     * blockshow模板(父级)
     * @var array
     */
    protected static $widget = [];

    /**
     * 标签列表
     * block 块标签
     * level 嵌套层次
     * @var array
     */
    public $tags = [
        'foreach' => ['block' => true, 'level' => 5],
        'list'    => ['block' => true, 'level' => 5],
        'if'      => ['block' => true, 'level' => 5],
        'elseif'  => ['block' => false],
        'else'    => ['block' => false],
        'js'      => ['block' => false],
        'css'     => ['block' => false],
        'include' => ['block' => false],
        'extend'  => ['block' => false],
        'blade'   => ['block' => false],
        'parent'  => ['block' => false],
        'block'   => ['block' => true, 'level' => 5],
        'widget'  => ['block' => true, 'level' => 5],
        'php'     => ['block' => true, 'level' => 5],
    ];

    /**
     * CSS处理
     * @param array $attr
     * @return string
     */
    public function _css($attr)
    {
        $attr['file'] = $this->replaceConstants($attr['file']);
        return '<link type="text/css" rel="stylesheet" href="' . $attr['file'] . '"/>';
    }

    /**
     * JS处理
     * @param array $attr
     * @return string
     */
    public function _js($attr)
    {
        $attr['file'] = $this->replaceConstants($attr['file']);
        return '<script type="text/javascript" src="' . $attr['file'] . '"></script>';
    }

    /**
     * list标签处理
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _list($attr, $content)
    {
        $from = $attr['from']; // 变量
        $name = $attr['name']; // name名去除$
        $empty = isset($attr['empty']) ? $attr['empty'] : ''; // 默认值
        $row = isset($attr['row']) ? $attr['row'] : 100; // 显示条数
        $step = isset($attr['step']) && $attr['step'] > 0 ? $attr['step'] : 1; // 间隔
        $start = isset($attr['start']) ? max(0, $attr['start'] - 1) : 0; // 开始数
        $php = <<<php
        <?php
        if (empty($from)) {
            echo '$empty';
        } else {
            \$_name = substr('$name', 1);
            \$funsent['list'][\$_name]['first'] = false;
            \$funsent['list'][\$_name]['last'] = false;
            \$funsent['list'][\$_name]['index'] = 0;
            \$funsent['list'][\$_name]['total'] = 0;
            \$id = 0; \$key = $start; \$_tmp = $from;
            for (\$index = $start, \$cnt = count($from); \$index < $cnt; \$index++) {
                $name = \$_tmp[\$key]; \$key +=$step; 
                \$funsent['list'][\$_name]['first'] = (\$index == $start);
                \$funsent['list'][\$_name]['index'] = ++\$id;
                \$funsent['list'][\$_name]['last'] = (\$id >= $row) || (!isset(\$_tmp[\$key]));
            ?>
php;
        $php .= $content;
        $php .= "<?php if (\$funsent['list'][\$_name]['last']) { break; } }} ?>";
        return $php;
    }

    /**
     * foreach标签处理
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _foreach($attr, $content)
    {
        if (isset($attr['key'])) {
            $php = "<?php if (is_array({$attr['from']}) || is_object({$attr['from']})) { foreach ({$attr['from']} as {$attr['key']} => {$attr['value']}) { ?>";
        } else {
            $php = "<?php if (is_array({$attr['from']}) || is_object({$attr['from']})) { foreach ({$attr['from']} as {$attr['value']}) { ?>";
        }
        $php .= $content;
        $php .= '<?php }} ?>';
        return $php;
    }

    /**
     * if标签处理
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _if($attr, $content)
    {
        $php= "<?php if({$attr['value']}){ ?>
                $content
               <?php } ?>";
        return $php;
    }

    /**
     * elseif标签处理
     * @param array $attr
     * @return string
     */
    public function _elseif($attr)
    {
        return "<?php } elseif ({$attr['value']}) { ?>";
    }

    /**
     * else标签处理
     * @return string
     */
    public function _else()
    {
        return "<?php } else { ?>";
    }

    /**
     * php标签处理
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _php($attr, $content)
    {
        return "<?php $content; ?>";
    }

    /**
     * include处理，加载模板文件
     * @param array $attr
     * @return string
     */
    public function _include($attr)
    {
        $file = $this->replaceConstants($attr['file']);
        return (new Base())->make($file)->compile()->getCompileContent();
    }

    /**
     * extend处理，块布局时引入布局页的bladeshow块
     * @param array $attr
     * @return string
     */
    public function _extend($attr)
    {
        $file = $this->replaceConstants($attr['file']);
        return (new Base())->make($file)->compile()->getCompileContent();
    }

    /**
     * blade处理，布局模板定义的块(父级)
     * @param array $attr
     * @return string
     */
    public function _blade($attr)
    {
        return "<!--blade_{$attr['name']}-->";
    }

    /**
     * block处理，视图模板定义的内容(子级)
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _block($attr, $content)
    {
        $this->content = str_replace("<!--blade_{$attr['name']}-->", $content, $this->content);
    }

    /**
     * widget处理，布局模板定义用于显示在视图模板的内容(父模板)
     * @param array $attr
     * @param string $content
     * @return string
     */
    public function _widget($attr, $content)
    {
        self::$widget[$attr['name']] = $content;
    }

    /**
     * parent处理，视图模板引用布局模板(子模板)
     * @param array $attr
     * @return string
     */
    public function _parent($attr)
    {
        $content = self::$widget[$attr['name']];
        foreach ($attr as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
}
