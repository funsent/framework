<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\validater\build;

use funsent\db\Db;
use funsent\captcha\Captcha;

/**
 * 验证处理
 * @package funsent\validater\build
 */
class VaAction
{
    /**
     * 字段为空时验证
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function isnull($field, $value, $params, $data)
    {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
        return true;
    }

    /**
     * 字段是否存在验证
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function required($field, $value, $params, $data)
    {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
        return true;
    }

    /**
     * 验证码验证
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function captcha($field, $value, $params, $data)
    {
        return isset($data[$field]) && strtoupper($data[$field]) == Captcha::get();
    }

    /**
     * 存在字段时验证
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function exists($field, $value, $params, $data)
    {
        return isset($data[$field]) ? false : true;
    }

    /**
     * 验证字段值唯一
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function unique($field, $value, $params, $data)
    {
        $args = explode(',', $params);
        $db = Db::table($args[0])->where($field, $value);
        if (isset($data[$args[1]])) {
            $db->where($args[1], '<>', $data[$args[1]]);
        }
        return empty($value) || ! $db->pluck($field) ? true : false;
    }

    /**
     * 邮箱验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function email($name, $value, $params)
    {
        $preg = "/^([a-zA-Z0-9_\-\.])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{1,3}){1,2})$/i";
        if (preg_match($preg, $value)) {
            return true;
        }
    }

    /**
     * 验证用户名长度
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function user($field, $value, $params, $data)
    {
        $params = explode(',', $params);
        return preg_match('/^[\x{4e00}-\x{9fa5}a-z0-9]{' . ($params[0] - 1) . ',' . ($params[1] - 1) . '}$/ui', $value) ? true : false;
    }

    /**
     * 邮编验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function zipCode($name, $value, $params)
    {
        if (preg_match("/^\d{6}$/i", $value)) {
            return true;
        }
    }

    /**
     * 最大长度验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function maxlen($name, $value, $params)
    {
        if (mb_strlen($value, 'utf-8') <= $params) {
            return true;
        }
    }

    /**
     * 最小长度验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function minlen($name, $value, $params)
    {
        if (mb_strlen($value, 'utf-8') >= $params) {
            return true;
        }
    }

    /**
     * url验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function http($name, $value, $params)
    {
        if (preg_match("/^(http[s]?:)?(\/{2})?([a-z0-9]+\.)?[a-z0-9]+(\.(com|cn|cc|org|net|com.cn))$/i", $value)) {
            return true;
        }
    }

    /**
     * 固定电话格式验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function tel($name, $value, $params)
    {
        if (preg_match("/(?:\(\d{3,4}\)|\d{3,4}-?)\d{8}/", $value)) {
            return true;
        }
    }

    /**
     * 手机号长度验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function phone($name, $value, $params)
    {
        if (preg_match("/^\d{11}$/", $value)) {
            return true;
        }
    }

    /**
     * 身份证长度验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function identity($name, $value, $params)
    {
        if (preg_match("/^(\d{15}|\d{18})$/", $value)) {
            return true;
        }
    }

    /**
     * 用户名合法验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function range($name, $value, $params)
    {
        $len = mb_strlen($value, 'utf-8');
        $params = explode(',', $params);
        if ($len >= $params[0] && $len <= $params[1]) {
            return true;
        }
    }

    /**
     * 数字范围验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function num($name, $value, $params)
    {
        $params = explode(',', $params);
        if (intval($value) >= $params[0] && intval($value) <= $params[1]) {
            return true;
        }
    }

    /**
     * 正则验证
     * @param string $name
     * @param mixed $value
     * @param string $preg
     * @return boolean
     */
    public function regex($name, $value, $preg)
    {
        if (preg_match($preg, $value)) {
            return true;
        }
    }

    /**
     * 比较验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @param array $data
     * @return boolean
     */
    public function confirm($name, $value, $params, $data)
    {
        if ($value == $data[$params]) {
            return true;
        }
    }

    /**
     * 中文验证
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return boolean
     */
    public function china($name, $value, $params)
    {
        if (preg_match('/^[\x{4e00}-\x{9fa5}a-z0-9]+$/ui', $value)) {
            return true;
        }
    }
}
