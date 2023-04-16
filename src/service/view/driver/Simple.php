<?php

/**
 * funsent - the web application framework by PHP
 * Copyright(c) funsent.com Inc. All Rights Reserved.
 * 
 * @version $Id$
 * @author  yanggf <2018708@qq.com>
 * @see     http://www.funsent.com/
 * @license MIT
 */

declare(strict_types=1);

namespace funsent\service\view;

use funsent\view\contract\ViewAbstract;
use funsent\view\contract\ViewInterface;
use funsent\view\driver\simple\Simple as SimpleEngine;

/**
 * Simple模板引擎实现的视图服务
 * 
 * @package funsent\view\driver
 */
class Simple extends ViewAbstract implements ViewInterface
{
    /**
     * 获取模板引擎实例
     * 
     * @param array $option
     * @return \funsent\view\driver\simple\Simple
     */
    public static function instance(array $option)
    {
        // 实例化模板引擎
        static $instance = null;

        if (is_null($instance)) {
            $instance = new SimpleEngine();
            $instance->setDebug($option['debug'])
                     ->setTags($option['tags'])
                     ->setLeftDelimiter($option['left_delimiter'])
                     ->setRightDelimiter($option['right_delimiter'])
                     ->setCacheLifetime($option['cache_lifetime'])
                     ->setCacheCheck($option['cache_check'])
                     ->setFormCsrf($option['form_csrf']);
        }

        // 设置路径
        $instance->setTemplatePath($option['view_path'])
                 ->setCompilePath($option['compile_path'])
                 ->setCachePath($option['cache_path']);
        return $instance;
    }
}
