<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\repository\contract;

use funsent\model\repository\Rule;

/**
 * Interface RuleInterface
 * @package funsent\model\repository\contract
 */
interface RuleInterface
{
    /**
     * 重置规则
     * @return $this
     */
    public function resetRule();

    /**
     * 跳过规则，不执行任何规则
     * @param boolean $status
     * @return $this
     */
    public function skipRule($status = true);

    /**
     * 获取所有规则
     * @return mixed
     */
    public function getRule();

    /**
     * 获取指定的规则
     * @param \funsent\model\repository\Rule $Rule
     * @return $this
     */
    public function getByRule(Rule $Rule);

    /**
     * 添加规则
     * @param \funsent\model\repository\Rule $Rule
     * @return $this
     */
    public function pushRule(Rule $Rule);

    /**
     * 应用所有规则
     * @return $this
     */
    public function applyRule();
}
