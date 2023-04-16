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
 * 安全接口
 */
interface SecurityInterface
{
    /**
     * 加密
     * @param string $str 字符串
     * @param integer $lifetime 有效时间，单位秒，0表示永久有效
     * @return string
     */
    public function encrypt($str, $lifetime = 0);

    /**
     * 解密
     * @param string $str 字符串
     * @return string
     */
    public function decrypt($str);
}
