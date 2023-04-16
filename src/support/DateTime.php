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

namespace funsent\helper;

use Carbon\Carbon as BaseCarbon;

/**
 * 日期时间工具类
 */
class DateTime extends BaseCarbon
{
    public function isValid($datetime)
    {
        return date('Y-m-d H:i:s', strtotime($datetime)) == $datetime;
    }
}
