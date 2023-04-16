<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\build;

use funsent\db\Db;
use funsent\validate\Validate;
use funsent\validate\build\VaAction;
use funsent\error\exception\RuntimeException;

/**
 * Trait Validater
 * @package funsent\model\build
 */
trait Validater
{
    /**
     * 验证规则
     * @var array
     */
    protected $validateRules = [];

    /**
     * 验证错误
     * @var array
     */
    protected $error = [];

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置错误提示
     * @param array|string $error
     * @return void
     */
    public function setError($error)
    {
        $error = is_array($error) ? $error : [$error];
        $this->error = array_merge($this->error, $error);
    }

    /**
     * 自动验证
     * @return boolean
     * @throws \funsent\error\exception\RuntimeException
     */
    final protected function autoValidate()
    {
        $this->setError([]);
        if (empty($this->original)) {
            throw new RuntimeException(sprintf('Data can not be empty %s()', __METHOD__));
        }
        $vaAction = new VaAction(); // 验证库
        if (empty($this->validateRules)) {
            return true;
        }
        $data = &$this->original;
        foreach ($this->validateRules as $rule) {
            $rule[3] = isset($rule[3]) ? $rule[3] : self::VALIDATE_EXIST;
            if ($rule[3] == self::VALIDATE_EXIST && ! isset($data[$rule[0]])) {
                // 存在字段时验证
                continue;
            } elseif ($rule[3] == self::VALIDATE_NOT_EMPTY && empty($data[$rule[0]])) {
                // 不为空时验证
                continue;
            } elseif ($rule[3] == self::VALIDATE_EMPTY && ! empty($data[$rule[0]])) {
                // 值为空时验证
                continue;
            } elseif ($rule[3] == self::VALIDATE_NOT_EXIST && isset($data[$rule[0]])) {
                // 不存在字段时验证
                continue;
            } elseif ($rule[3] == self::VALIDATE_MUST) {
                // 必须验证
            }
            $rule[4] = isset($rule[4]) ? $rule[4] : self::MODEL_INSERT_UPDATE;
            if ($rule[4] != $this->action() && $rule[4] != self::MODEL_INSERT_UPDATE) {
                continue;
            }
            $field = $rule[0];
            $actions = explode('|', $rule[1]);
            $error = $rule[2];
            $value = isset($data[$field]) ? $data[$field] : '';
            foreach ($actions as $action) {
                $info = explode(':', $action);
                $method = $info[0];
                $params = isset($info[1]) ? $info[1] : '';
                if (method_exists($this, $method)) { // 类方法验证
                    if ($this->$method($field, $value, $params, $data) != true) {
                        $this->error[$field] = $error;
                    }
                } elseif (method_exists($vaAction, $method)) { // 验证器验证
                    if ($vaAction->$method($field, $value, $params, $data) != true) {
                        $this->error[$field] = $error;
                    }
                } elseif (function_exists($method)) { // 函数验证
                    if ($method($value) != true) {
                        $this->error[$field] = $error;
                    }
                } elseif (substr($method, 0, 1) == '/') { // 正则验证
                    if (!preg_match($method, $value)) { 
                        $this->error[$field] = $error;
                    }
                }
            }
        }
        Validate::respond($this->error);
        return $this->error ? false : true;
    }

    /**
     * 验证字段值唯一，用于自动验证
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $param 参数
     * @param array $data  提交数据
     * @return boolean 验证状态
     */
    final protected function unique($field, $value, $param, $data)
    {
        $db = Db::table($this->table)->where($field, $value);
        if ($this->action() == self::MODEL_UPDATE) {
            $db->where($this->pk, '<>', $this->data[$this->pk]);
        }
        if (empty($value) || !$db->get()) {
            return true;
        }
    }
}
