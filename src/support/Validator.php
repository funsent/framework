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

/**
 * 验证工具类
 */
class Validator
{
    /**
     * 是否为日期时间格式字符串
     *
     * @param string $str
     * @param string $format
     * @return bool
     */
    public static function isDateTimeFormat($str, $format = 'Y-m-d H:i:s')
    {
        $datetime = \DateTime::createFromFormat($format, $str);
        return $datetime && $datetime->format($format) == $str;
    }

    /**
     * 是否为日期时间格式字符串
     *
     * @param string $str
     * @param string $format
     * @return bool
     */
    public static function isDateTimeStr($str, $format = 'Y-m-d H:i:s')
    {
        $time = strtotime($str);
        if (false === $time) {
            return false;
        }
        if ($str === date($format, $time)) {
            return true;
        }
        return false;
    }

    /**
     * 验证中国大陆身份证号
     * 
     * @param string $str
     * @return bool
     */
    public static function isIdCardNumber($str)
    {
        return IdCardNumber::check($str);
    }

    /**
     * 验证中国大陆邮政编码
     * 
     * @param integer $str
     * @return bool
     */
    public static function isPostcode($str)
    {
        return preg_match('/^\d{6}$/', $str) ? true : false;
    }

    /**
     * 验证中国大陆手机号码
     * @param integer $str
     * @return bool
     */
    public static function isMobile($str)
    {
        return preg_match('/^1[3456789]\d{9}$/', $str) ? true : false;
    }

    /**
     * 验证中国大陆电话号码
     * 
     * @param string $str
     * @return bool
     */
    public static function isPhone($str)
    {
        return preg_match('/\d{3,4}-\d{6,8}/', $str) ? true : false;
    }
    public static function isTel($str)
    {
        return self::isPhone($str);
    }

    /**
     * 验证名称，限制中文、英文字母、0-9数字
     * 
     * @param string $str
     * @param integer $min
     * @param integer $max
     * @return bool
     */
    public static function isName($str, $min = 2, $max = 30)
    {
        $regex = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]{' . $min . ',' . $max . '}$/u';
        return preg_match($regex, $str) ? true : false;
    }

    /**
     * 验证用户名，限制中文、英文字母、0-9数字和字符_-
     * 
     * @param string $str
     * @param integer $min
     * @param integer $max
     * @return bool
     */
    public static function isUsername($str, $min = 2, $max = 32)
    {
        $regex = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_-]{' . $min . ',' . $max . '}$/u';
        return preg_match($regex, $str) ? true : false;
    }

    /**
     * 验证密码，限制英文字母、0-9数字、特殊字符：~!@#$%^&*()_+-=;:?,.
     * 
     * @param string $str
     * @param integer $min
     * @param integer $max
     * @return bool
     */
    public static function isPassword($password, $min = 4, $max = 32)
    {
        $chars = str_split('~!@#$%^&*()_+-=;:?,.');
        $password = str_replace($chars, '', $password);
        $regex = '/^[a-zA-Z0-9]{' . $min . ',' . $max . '}$/i';
        return preg_match($regex, $password) ? true : false;
    }

    /**
     * 验证中文汉字
     * 
     * @param string $str
     * @return bool
     */
    public static function isChinese($str)
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}]+$/i', $str) ? true : false;
    }

    /**
     * 验证英文字母
     * 
     * @param string $str
     * @return bool
     */
    public static function isEnglish($str)
    {
        return preg_match('/^[a-zA-Z]+$/', $str) ? true : false;
    }

    /**
     * 验证电子邮箱
     * 
     * @param string $str
     * @return bool
     */
    public static function isEmail($str)
    {
        $regex = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i';
        return preg_match($regex, $str) ? true : false;
    }

    /**
     * 验证URL
     * 
     * @param string $str
     * @return bool
     */
    public static function isUrl($str)
    {
        $regex = '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/i';
        return preg_match($regex, $str) ? true : false;
    }
}
