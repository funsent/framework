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

/**
 * Trait Relation
 * @package funsent\model\build
 */
trait Relation
{
    /**
     * 一对一关联
     * @param string $class 关联模型
     * @param integer $foreignKey 关联表关联字段
     * @param integer $localKey 本模型字段
     * @return mixed
     */
    protected function hasOne($class, $foreignKey = 0, $localKey = 0)
    {
        $foreignKey = $foreignKey ?: $this->getTable() . '_' . $this->getPk();
        $localKey = $localKey ?: $this->getPk();
        return (new $class())->where($foreignKey, $this[$localKey])->first();
    }

    /**
     * 一对多关联
     * @param string $class 关联模型
     * @param string $foreignKey 关联表关联字段
     * @param string $localKey 本模型字段
     * @return mixed
     */
    protected function hasMany($class, $foreignKey = '', $localKey = '')
    {
        $foreignKey = $foreignKey ?: $this->getTable() . '_' . $this->getPk();
        $localKey = $localKey ?: $this->getPk();
        return (new $class())->where($foreignKey, $this[$localKey])->get();
    }

    /**
     * 相对关联
     * @param string $class
     * @param string $parentKey
     * @param string $localKey
     * @return mixed
     */
    protected function belongsTo($class, $localKey = null, $parentKey = null)
    {
        $instance = new $class(); // 父表
        $parentKey = $parentKey ?: $instance->getPk();
        $localKey = $localKey ?: $instance->getTable() . '_' . $instance->getPk();
        return $instance->where($parentKey, $this[$localKey])->first();
    }

    /**
     * 多对多关联
     * @param string $class 关联中间模型
     * @param string $middleTable 中间表
     * @param string $localKey 主表字段
     * @param string $foreignKey 关联表字段
     * @return mixed
     */
    protected function belongsToMany($class, $middleTable = '', $localKey = '', $foreignKey = '')
    {
        $instance = new $class;
        $middleTable = $middleTable ?: $this->getTable() . '_' . $instance->getTable();
        $localKey = $localKey ?: $this->table . '_' . $this->pk;
        $foreignKey = $foreignKey ?: $instance->getTable() . '_' . $instance->getPrimaryKey();
        $middle = Db::table($middleTable)->where($localKey, $this[$this->pk])->lists($foreignKey);
        return $instance->whereIn($instance->getPk(), array_values($middle))->get();
    }
}
