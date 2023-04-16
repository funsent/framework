<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\repository\rule;

use funsent\model\Model;
use funsent\model\repository\Rule;
use funsent\model\repository\Repository;

/**
 * 基于模型数据仓库的{{name}}扩展查询规则
 * @package {{app}}\common\repository\rule
 */
class {{name}}Rule extends Rule
{
    /**
     * 限制数量
     * @var integer
     */
    protected $limit;

    /**
     * 构造方法
     * @param integer $limit
     */
    public function __construct($limit = 10)
    {
        $this->limit = $limit;
    }

    /**
     * 应用规则
     * @param Model
     * @param Repository $repository
     * @return Model
     */
    public function apply($model, Repository $repository)
    {
        return $model->limit($this->limit);
    }
}
