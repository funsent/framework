<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\repository;

use funsent\model\Model;
use funsent\model\repository\Repository;

/**
 * 查询规则
 * @package funsent\model\repository
 */
abstract class Rule
{
    /**
     * 应用规则
     * @param string|\funsent\model\Model $model
     * @param \funsent\model\repository\Repository $repository
     * @return mixed
     */
    public abstract function apply($model, Repository $repository);
}
