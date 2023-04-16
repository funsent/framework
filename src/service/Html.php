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

namespace funsent\support;

use funsent\contract\Htmlable;

/**
 * Html对象
 */
class Html implements Htmlable
{
    /**
     * Html字符串
     *
     * @var string
     */
    protected $htmlStr;

    /**
     * 创建一个Html字符串对象实例
     *
     * @param string $htmlStr
     * @return void
     */
    public function __construct($htmlStr)
    {
        $this->htmlStr = $htmlStr;
    }

    /**
     * 返回Html字符串
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->htmlStr;
    }

    /**
     * 返回Html字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
