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
 * JSON化接口
 * 
 * @method string toJson(int $options)
 */
interface Jsonable
{
    /**
     * 转化为JSON字符串表示
     * 
     * @param int $options
     * @return string
     */
    public function toJson($options = 0);
}
