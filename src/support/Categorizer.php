<?php

/**
 * funsent - the web application framework by PHP
 * Copyright(c) funsent.com Inc. All Rights Reserved.
 * 
 * @version $Id$
 * @author  yanggf <2018708@qq.com>
 * @see     http://www.funsent.com/
 * @license MIT
 */

declare(strict_types=1);

namespace funsent\helper;

/**
 * 无限极分类工具类
 */
class Categorizer
{
    /**
     * 缓存有效时间，单位秒
     * 
     * @var int
     */
    protected $lifetime = 7 * 24 * 3600;

    /**
     * 表前缀
     * 
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * 数据库连接信息
     * 
     * @var null
     */
    protected $connection = '';

    /**
     * 唯一标识字段
     * 
     * @var string
     */
    protected $idField = 'id';

    /**
     * 上级标识字段
     * 
     * @var string
     */
    protected $parentIdField = 'parent_id';

    /**
     * 查询字段
     * 
     * @var array
     */
    protected $selectFields = ['id', 'parent_id', 'name'];

    /**
     * 排序字段
     * 
     * @var array
     */
    protected $orderFields = ['parent_id' => 'ASC', 'order' => 'DESC', 'id' => 'ASC'];

    /**
     * 初始化构造函数
     * 
     * @param array $option 数组参数，包括 table_prefix、connection、id_field、parent_id_field、select_fields、order_fields
     */
    public function __construct($option = [])
    {
        if (isset($option['lifetime'])) {
            $this->lifetime =  $option['lifetime'];
        }
        if (isset($option['table_prefix'])) {
            $this->tablePrefix =  $option['table_prefix'];
        }
        if (isset($option['connection'])) {
            $this->connection = $option['connection'];
        }
        if (isset($option['id_field'])) {
            $this->idField = $option['id_field'];
        }
        if (isset($option['parent_id_field'])) {
            $this->parentIdField = $option['parent_id_field'];
        }
        if (isset($option['select_fields'])) {
            $this->selectFields = $option['select_fields'];
        }
        if (isset($option['order_fields'])) {
            $this->orderFields = $option['order_fields'];
        }
    }

    /**
     * 获取指定分类数据，返回二维数组
     *
     * @param string $key 键名，为不带前缀的表名
     * @param int $value 字段值
     * @param string $field 字段名
     * @return mixed
     */
    public function get($key, $value, $field = '')
    {
        $field = strlen(trim($field)) ? trim($field) : $this->idField;
        $categories = [];
        $rows = $this->getAll($key);
        for ($i = 0, $cnt = count($rows); $i < $cnt; $i++) {
            if ($rows[$i][$field] == $value) {
                $categories[] = $rows[$i];
            }
        }
        return $categories;
    }

    /**
     * 获取分类数据，返回二维数组
     *
     * @param string $key 键名，为不带前缀的表名
     * @param int $parentId 上级分类ID，用于递归查找
     * @param boolean $statDepth 是否以当前上级分类ID为基准统计分类深度
     * @param mixed $orderFields 字段排序，数组或者字符串格式
     * @return array
     */
    public function getAll($key, $parentId = 0, $statDepth = true, $orderFields = '')
    {
        $categories = [];
        if ($value = S($key)) {
            $rows = unserialize($value);
            $this->rebuild($parentId, $rows, $categories, $statDepth);
            return $categories;
        }
        if (is_array($orderFields)) {
            $orderFields = $orderFields ? $orderFields : $this->orderFields;
        } else {
            $orderFields = strlen(trim($orderFields)) ? trim($orderFields) : $this->orderFields;
        }
        $rows = M($key, $this->tablePrefix, $this->connection)
            ->field($this->selectFields)
            ->order($orderFields)
            ->select();
        $this->rebuild($parentId, $rows, $categories, $statDepth);
        S($key, serialize($categories), $this->lifetime);
        return $categories;
    }

    /**
     * 重建分类
     *
     * @param int $parentId 上级分类ID，用于递归查找
     * @param array $rows 待重建的分类数据
     * @param array $categories 重建后的分类数据
     * @param boolean $statDepth 是否以当前上级分类ID为基准统计分类深度
     * @param int $depth 分类深度
     * @param string $merge 上级分类ID合并
     * @return null
     */
    protected function rebuild($parentId, $rows, &$categories, $statDepth = false, $depth = 1, $merge = '')
    {
        for ($i = 0, $cnt = count($rows); $i < $cnt; $i++) {
            if ($rows[$i][$this->parentIdField] == $parentId) {
                if ($statDepth) {
                    $rows[$i]['depth'] = $depth;
                    $rows[$i]['parent_id_merge'] = strlen($merge) ? $merge . ',' : $merge;
                }
                $categories[] = $rows[$i];
                $id = $rows[$i][$this->idField];
                $this->rebuild($id, $rows, $categories, $statDepth, $depth + 1, $merge . ',' . $id);
            }
        }
    }
}
