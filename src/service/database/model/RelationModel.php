<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model;

define('HAS_ONE', 'HAS_ONE');
define('HAS_MANY', 'HAS_MANY');
define('BELONGS_TO', 'BELONGS_TO');
define('MANY_TO_MANY', 'MANY_TO_MANY');

use funsent\db\Db;
use funsent\model\Model;

/**
 * 关联模型
 * @package funsent\model
 */
class RelationModel extends Model
{
    /**
     * 关联模型定义
     * @var array
     */
    public $relation = [];

    /**
     * 查找一条数据
     * @param mixed $id
     * @return mixed
     */
    public function find($id)
    {
        if ($instance = parent::find($id)) {
            $data = $this->relationSelect([$instance]);
            return current($data);
        } else {
            return $instance;
        }
    }

    /**
     * 查找所有数据
     * @param mixed $id
     * @return mixed
     */
    public function all()
    {
        if ($instance = parent::all()) {
            $data = $this->relationSelect($instance);
            return $data;
        } else {
            return $instance;
        }
    }

    /**
     * 关联查询
     * @param mixed $instance
     * @return mixed
     */
    private function relationSelect( $instance )
    {
        foreach ( $this->relation as $table => $relation ) {
            $foreignKey = $relation['foreign_key'];
            $parentKey = $relation['parent_key'];
            switch ($relation['type']) {
                case HAS_ONE:
                    foreach ($instance as $key => $value) {
                        $instance[$key]['_' . $table] = Db::table($table)->where($parentKey, $value[$foreignKey])->first();
                    }
                    break;
                case HAS_MANY:
                    foreach ($instance as $key => $value) {
                        $instance[$key]['_' . $table] = Db::table($table)->where($parentKey, $value[$foreignKey])->get();
                    }
                    break;
                case BELONGS_TO:
                    foreach ($instance as $key => $value) {
                        $instance[$key]['_' . $table] = Db::table($table)->where($foreignKey, $value[$parentKey])->get();
                    }
                    break;
                case MANY_TO_MANY:
                    foreach ($instance as $key => $value) {
                        $rel = Db::table($relation['relation_table'])->where($parentKey, $value->getPrimaryKey())->lists($foreignKey);
                        if ($rel) {
                            $instance[$key]['_' . $table] = Db::table($table)->whereIn($foreignKey, $rel)->get();
                        }
                    }
                    break;
            }
        }
        return $instance;
    }

    /**
     * 插入数据
     * @param array $data
     * @return mixed
     */
    public function insert(array $data = [])
    {
        if ($instance = parent::insert($data)) {
            $result = $this->relationInsert($instance, $data);
            return $result;
        }
        return $instance;
    }

    /**
     * 关联插入
     * @param mixed $instance
     * @param array $data
     * @return mixed
     */
    private function relationInsert($instance, $data)
    {
        foreach ($this->relation as $table => $relation) {
            $foreignKey = $relation['foreign_key'];
            $parentKey = $relation['parent_key'];
            switch ($relation['type']) {
                case HAS_ONE:
                case HAS_MANY:
                    $id = Db::table($table)->insert($data['_' . $table]);
                    $instance[$parentKey] = $id;
                    $instance->save();
                    break;
                case BELONGS_TO:
                    $data['_' . $table][$foreignKey] = $instance->getPrimaryKey();
                    Db::table($table)->insert($data['_' . $table]);
                    break;
                case MANY_TO_MANY:
                    $id = Db::table($table)->insert($data['_' . $table]);
                    $tmp[$foreignKey] = $id;
                    $tmp[$parentKey] = $instance->getPrimaryKey();
                    Db::table($relation['relation_table'])->insert($tmp);
                    break;
            }
        }
        return $instance;
    }

    /**
     * 更新数据
     * @param array $data
     * @return mixed
     */
    public function save(array $data = [])
    {
        if ($instance = parent::save($data)) {
            $this->relationSave($this, $data);
        }
        return $instance;
    }

    /**
     * 关联更新
     * @param mixed $instance
     * @param array $data
     * @return mixed
     */
    private function relationSave($instance, $data)
    {
        foreach ($this->relation as $table => $relation) {
            $foreignKey = $relation['foreign_key'];
            $parentKey = $relation['parent_key'];
            switch ($relation['type']) {
                case HAS_ONE:
                case HAS_MANY:
                case BELONGS_TO:
                case MANY_TO_MANY:
                    Db::table($table)->update($data[substr($table, 1)]);
                    break;
            }
        }
        return $instance;
    }

    /**
     * 删除数据
     * @return mixed
     */
    public function delete()
    {
        if ($state = parent::delete()) {
            $this->relationDelete($instance);
        }
        return $state;
    }

    /**
     * 关联删除
     * @param mixed $instance
     * @return mixed
     */
    private function relationDelete($instance)
    {
        foreach ($this->relation as $table => $relation) {
            $foreignKey = $relation['foreign_key'];
            $parentKey = $relation['parent_key'];
            switch ($relation['type']) {
                case HAS_ONE:
                case HAS_MANY:
                    break;
                case BELONGS_TO:
                    Db::table($table)->where($foreignKey, $instance->getPrimaryKey())->delete();
                    break;
                case MANY_TO_MANY:
                    Db::table($relation['relation_table'])->where($parentKey, $instance->getPrimaryKey())->delete();
                    break;
            }
        }
        return $instance;
    }
}
