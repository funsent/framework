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
 * Trait Filter
 * @package funsent\model\build
 */
trait Filter
{
    /**
     * 过滤规则
     * @var array
     */
    protected $filterRules = [];

    /**
     * 自动过滤
     * @return boolean|void
     */
    final protected function autoFilte()
    {
        if (empty($this->filterRules)) {
            return;
        }
        $data = &$this->original;
        foreach ($this->filterRules as $rule) {
            $rule[1] = isset($rule[1]) ? $rule[1] : self::WRITE_EXIST;
            $rule[2] = isset($rule[2]) ? $rule[2] : self::MODEL_INSERT_UPDATE;
            if ($rule[1] == self::FILTER_EXIST && ! isset($data[$rule[0]])) {
                // 存在字段时过滤
                continue;
            } elseif ($rule[1] == self::FILTER_NOT_EMPTY && empty($data[$rule[0]])) {
                // 值不为空时过滤
                continue;
            } elseif ($rule[1] == self::FILTER_EMPTY && ! empty($data[$rule[0]])) {
                // 值为空时过滤
                continue;
            } elseif ($rule[1] == self::FILTER_NOT_EXIST && isset($data[$rule[0]])) {
                // 不存在字段时过滤
                continue;
            } elseif ($rule[1] == self::FILTER_MUST) {
                // 必须过滤
            }
            if ($rule[2] == $this->action() || $rule[2] == self::MODEL_INSERT_UPDATE) {
                unset($data[$rule[0]]);
            }
        }
        return true;
    }
}
