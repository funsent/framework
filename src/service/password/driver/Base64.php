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

namespace funsent\support\password\driver;

/**
 * Base64驱动的会话实现类
 * @package funsent
 * @author yanggf
 */
class Base64
{
    /**
     * 加密
     * @param string $str
     * @param string $key 秘钥
     * @param integer $lifetime 有效时间，单位秒
     * @return string
     */
    public function encrypt($data, $key = '', $lifetime = 0)
    {
        $expire = sprintf('%010d', $lifetime ? $lifetime + time() : 0);
        $key = $this->key($key);
        $key = md5($key);
        $data = base64_encode($expire . $data);
        $x = 0;
		$len = strlen($data);
        $l = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }
        return $str;
    }

    /**
     * 解密
     * @param string $str
     * @param string $key 秘钥
     * @return string
     */
    public function decrypt($data, $key = '')
    {
        $key = $this->key($key);
        $key = md5($key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        $data = base64_decode($str);
        $expire = substr($data, 0, 10);
        if ($expire > 0 && $expire < time()) {
            return '';
        }
        $data = substr($data, 10);
        return $data;
    }
}
