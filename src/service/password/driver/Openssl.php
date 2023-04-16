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
 * Openssl驱动的会话实现类 PHP5.3.3+
 * @package funsent
 * @author yanggf
 */
class Openssl
{
    /**
     * 加密算法，参考 openssl_get_cipher_methods() 方法
     * @var string
     */
    protected $cipher = 'aes-256-cbc';

    /**
     * 加密
     * @param string $str 加密字符
     * @param string $key 密钥
     * @param integer $lifetime 有效时间，单位秒
     * @return string
     */
    public function encrypt($str, $key = '', $lifetime = 0)
    {
        $expire = sprintf('%011d', $lifetime ? $lifetime + time() : 0);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypt = openssl_encrypt($str, $this->cipher, $this->key($key), OPENSSL_RAW_DATA, $iv);
        return base64_encode($expire . $encrypt);
    }

    /**
     * 解密
     * @param string $str 解密字符
     * @param string $key 密钥
     * @return string
     */
    public function decrypt($str, $key = '')
    {
        $encrypted = base64_decode($str);
        
        $expire = substr($encrypted, 0, 10);
        if ($expire > 0 && $expire < time()) {
            return '';
        }
        $encrypted = substr($encrypted, 10);

        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key($key), OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
}
