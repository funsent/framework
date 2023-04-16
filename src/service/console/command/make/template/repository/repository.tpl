<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\repository;

use funsent\model\repository\Repository;

/**
 * 基于{{name}}模型的数据仓库
 * @package {{app}}\common\repository
 */
class {{name}}Repository extends Repository
{
    /**
     * 获取模型类名
     * @return string
     */
    public function model()
    {
        return {{name}}::class;
    }
}
