<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\build;

/**
 * Trait Writer
 * @package funsent\model\build
 */
trait Writer
{
    /**
     * 写入规则
     * @var array
     */
    protected $writeRules = [];

    /**
     * 自动写入
     * @return boolean|void
     */
    final protected function autoWrite()
    {
        if (empty($this->writeRules)) {
            return;
        }
        $data = &$this->original;
        foreach ($this->writeRules as $rule) {
            $rule[2] = isset($rule[2]) ? $rule[2] : 'string';
            $rule[3] = isset($rule[3]) ? $rule[3] : self::WRITE_EXIST;
            $rule[4] = isset($rule[4]) ? $rule[4] : self::MODEL_INSERT_UPDATE;
            if ($rule[3] == self::WRITE_EXIST && ! isset($data[$rule[0]])) {
                // 存在字段时写入
                continue;
            } elseif ($rule[3] == self::WRITE_NOT_EMPTY && empty($data[$rule[0]])) {
                // 值不为空时写入
                continue;
            } elseif ($rule[3] == self::WRITE_EMPTY && ! empty($data[$rule[0]])) {
                // 值为空时写入
                continue;
            } elseif ($rule[3] == self::WRITE_NOT_EXIST && isset($data[$rule[0]])) {
                // 不存在字段时写入
                continue;
            } elseif ($rule[3] == self::WRITE_MUST) {
                // 必须写入
            }
            if ($rule[4] == $this->action() || $rule[4] == self::MODEL_INSERT_UPDATE) {
                //为字段设置默认值
                if (empty($data[$rule[0]])) {
                    $data[$rule[0]] = '';
                }
                if ($rule[2] == 'method') {
                    $data[$rule[0]] = call_user_func_array([$this, $rule[1]], [$data[$rule[0]], $data]);
                } elseif ($rule[2] == 'function') {
                    $data[$rule[0]] = $rule[1]($data[$rule[0]]);
                } elseif ($rule[2] == 'string') {
                    $data[$rule[0]] = $rule[1];
                }
            }
        }
        return true;
    }
}
