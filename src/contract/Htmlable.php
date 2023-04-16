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

namespace funsent\contract;

/**
 * HTML化接口
 * 
 * @method string toHtml()
 */
interface Htmlable
{
    /**
     * HTML字符串表示
     * 
     * @return string
     */
    public function toHtml();
}
