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

namespace funsent\support\password;

/**
 * 密码
 */
class Password
{
    /**
     * 参数
     * @var array
     */
    protected $config = array(
        'key'  => 'a1dcec5a2587876267c9b7d13de2ad84',
    );

    /**
     * 构造方法
     * @param array $config
     */
    final public function __construct($config)
    {
        // 保存配置
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取秘钥
     * @param string $key
     * @return string
     */
    final public function key($key = '')
    {
        if (strlen($key) > 16) {
            $this->config['key'] = $key;
        }
        return base64_decode(hash('sha256', $this->config['key'], true));
    }

    /**
     * 单向加密字符串，空字符串不加密
     * @param string $str
     * @return string
     */
    public function crypt($str = '')
    {
        return empty($str) ? '' : md5(sha1($str));
    }

    /**
     * 生成密码
     * @param string $newStr
     * @return string
     */
    public function generate($newStr)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return md5(sha1($newStr));
        }
        return password_hash($newStr, PASSWORD_DEFAULT, ['cost' => 10]);
    }

    /**
     * 验证密码
     * @param string $newStr
     * @param string $oldStr
     * @return bool
     */
    public function verify($newStr, $oldStr)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return $newStr === $oldStr;
        }
        return password_verify($newStr, $oldStr);
    }
}
