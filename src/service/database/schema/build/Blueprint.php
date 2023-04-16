<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\schema\build;

use funsent\db\Db;
use funsent\config\Config;
use funsent\schema\Schema;

/**
 * 表结构生成器
 * @package funsent\schema\build
 */
class Blueprint
{
    /**
     * 表结构语句
     * @var array
     */
    protected $instruction = [];

    /**
     * 新增表时索引数据
     * @var array
     */
    protected $index = [];

    /**
     * 不带前缀的表
     * @var string
     */
    protected $noPreTable;

    /**
     * 带前缀的数据表
     * @var string
     */
    protected $table;

    /**
     * 添加或修改的字段
     * @var array
     */
    protected $field;

    /**
     * 操作类型，create新增表 alter修改表
     * @var string
     */
    protected $type;

    /**
     * 表注释
     * @var string
     */
    protected $tableComment;

    /**
     * 构造方法
     * @param string $table
     * @param string $type
     * @param string $comment
     */
    public function __construct($table, $type = '', $comment = '')
    {
        $this->noPreTable = $table;
        $this->table = Config::get('db.prefix') . $table;
        $this->type = $type;
        $this->tableComment = $comment;
    }

    /**
     * 创建表
     * @return mixed
     */
    public function create()
    {
        $sql = sprintf('CREATE TABLE %s (', $this->table);
        $sqls = [];
        foreach ($this->instruction as $n) {
            if (isset($n['unsigned'])) {
                $n['sql'] .= ' UNSIGNED';
            }
            if (!isset($n['null'])) {
                $n['sql'] .= ' NOT NULL';
            }
            if (isset($n['default'])) {
                $n['sql'] .= ' DEFAULT ' . $n['default'];
            }
            if (isset($n['comment'])) {
                $n['sql'] .= ' COMMENT ' . "'{$n['comment']}'";
            }
            $sqls[] = $n['sql'];
        }
        $sql .= implode(',', $sqls);
        if (!empty($this->index)) {
            $sql .= ',' . implode(',', $this->index);
        }
        $sql .= ') ENGINE=InnoDB DEFAULT CHARSET UTF8 COMMENT=' . "'{$this->tableComment}'";
        return Db::execute($sql);
    }

    /**
     * 修改字段
     * @return mixed
     */
    public function change()
    {
        $sql = sprintf('ALTER TABLE %s MODIFY', $this->table);
        foreach ($this->instruction as $n) {
            if (isset($n['unsigned'])) {
                $n['sql'] .= ' UNSIGNED';
            }
            if ( ! isset($n['null'])) {
                $n['sql'] .= ' NOT NULL';
            }
            if (isset($n['default'])) {
                $n['sql'] .= ' DEFAULT ' . $n['default'];
            }
            if (isset($n['comment'])) {
                $n['sql'] .= ' COMMENT ' . "'{$n['comment']}'";
            }
            return Db::execute($sql . $n['sql']);
        }
    }

    /**
     * 添加字段
     * @return mixed
     */
    public function add()
    {
        $sql = sprintf('ALTER TABLE %s ADD', $this->table);
        foreach ($this->instruction as $n) {
            if (!Schema::fieldExists($n['field'], $this->noPreTable)) {
                if (isset($n['unsigned'])) {
                    $n['sql'] .= ' UNSIGNED';
                }
                if (!isset($n['null'])) {
                    $n['sql'] .= ' NOT NULL';
                }
                if (isset($n['default'])) {
                    $n['sql'] .= ' DEFAULT ' . $n['default'];
                }
                if (isset($n['comment'])) {
                    $n['sql'] .= ' COMMENT ' . "'{$n['comment']}'";
                }
                return Db::execute($sql . $n['sql']);
            }
        }
    }

    /**
     * 当前更改的字段序号
     * @return string
     */
    protected function currentFieldKey()
    {
        return count($this->instruction) - 1;
    }

    /**
     * 格式化索引数据
     * @param string|array $field 字段列表
     * @param string $type 索引类型，index普通索引 unique唯一索引
     * @return string
     */
    protected function formatIndexData($field, $type = 'KEY')
    {
        $field = is_array($field) ? $field : [$field];
        $name  = implode('_', $field);
        $field = implode('`,`', $field);
        return " {$type} `{$name}` (`{$field}`) ";
    }

    /**
     * 添加普通索引
     * @param string|array $field
     * @return void
     */
    public function index($field = [])
    {
        $field = empty($field) ? $this->instruction[$this->currentFieldKey()]['field'] : $field;
        switch ($this->type) {
            case 'create':
                $this->index[] = $this->formatIndexData($field);
                break;
            case 'alter':
                $sql = sprintf('ALTER TABLE `%s` ADD %s', $this->table, $this->formatIndexData($field));
                Db::execute($sql);
                break;
        }
    }

    /**
     * 添加唯一索引
     * @param string $field
     * @return void
     */
    public function unique($field)
    {
        $field = empty($field) ? $this->instruction[$this->currentFieldKey()]['field'] : $field;
        switch ($this->type) {
            case 'create':
                $this->index[] = $this->formatIndexData($field);
                break;
            case 'alter':
                $sql = sprintf('ALTER TABLE `%s` ADD %s', $this->table, $this->formatIndexData($field, 'UNIQUE'));
                Db::execute($sql);
                break;
        }
    }

    /**
     * 删除主键
     * @return void
     */
    public function dropPrimary()
    {
        $sql = sprintf('ALTER TABLE `%s` DROP PRIMARY KEY', $this->table);
        Db::execute($sql);
    }

    /**
     * 删除普通索引
     * @param string $name
     */
    public function dropIndex($name)
    {
        $sql = sprintf('ALTER TABLE `%s` DROP INDEX %s', $this->table, $name);
        Db::execute($sql);
    }

    /**
     * 设置自增字段
     * @param string $field
     * @return $this
     */
    public function increment($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' int PRIMARY KEY AUTO_INCREMENT '];
        return $this;
    }

    /**
     * 设置timestamp字段
     * @return $this
     */
    public function timestamps()
    {
        $this->instruction[] = ['field' => 'created_at', 'sql' => " created_at datetime COMMENT '创建时间' "];
        $this->instruction[] = ['field' => 'updated_at', 'sql' => " updated_at datetime COMMENT '更新时间' "];
        return $this;
    }

    /**
     * 设置tinyint字段
     * @param string $field
     * @return $this
     */
    public function tinyInt($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' tinyint '];
        return $this;
    }

    /**
     * 设置enum字段
     * @param string $field
     * @param array $data
     * @return $this
     */
    public function enum($field, $data)
    {
        $this->field = $field;
        $this->instruction[]['sql'] = $field . " enum('" . implode("','", $data) . "') ";
        return $this;
    }

    /**
     * 设置integer字段
     * @param string $field
     * @param array $data
     * @return $this
     */
    public function integer($field)
    {
        $this->field = $field;
        $this->instruction[]['sql'] = $field . ' int ';
        return $this;
    }

    /**
     * 设置datetime字段
     * @param string $field
     * @return $this
     */
    public function datetime($field)
    {
        $this->field = $field;
        $this->instruction[]['sql'] = $field . ' datetime ';
        return $this;
    }

    /**
     * 设置date字段
     * @param string $field
     * @return $this
     */
    public function date($field)
    {
        $this->field = $field;
        $this->instruction[]['sql'] = $field . ' date ';
        return $this;
    }

    /**
     * 设置smallint字段
     * @param string $field
     * @return $this
     */
    public function smallint($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' smallint '];
        return $this;
    }

    /**
     * 设置mediumint字段
     * @param string $field
     * @return $this
     */
    public function mediumint($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' mediumint '];
        return $this;
    }

    /**
     * 设置decimal字段
     * @param string $field
     * @param integer $len 整数位数长度
     * @param integer $de 小数位数长度
     * @return $this
     */
    public function decimal($field, $len, $de)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . " decimal($len, $de) "];
        return $this;
    }

    /**
     * 设置float字段
     * @param string $field
     * @param integer $len 整数位数长度
     * @param integer $de 小数位数长度
     * @return $this
     */
    public function float($field, $len, $de)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . " float($len, $de) "];
        return $this;
    }

    /**
     * 设置double字段
     * @param string $field
     * @param integer $len 整数位数长度
     * @param integer $de 小数位数长度
     * @return $this
     */
    public function double($field, $len, $de)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . " double($len, $de) "];
        return $this;
    }

    /**
     * 设置char字段
     * @param string $field
     * @param integer $len 长度
     * @return $this
     */
    public function char($field, $len = 255)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . " char($len) "];
        return $this;
    }

    /**
     * 设置varchar字段
     * @param string $field
     * @param integer $len 长度
     * @return $this
     */
    public function string($field, $len = 255)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . " varchar($len) "];
        return $this;
    }

    /**
     * 设置text字段
     * @param string $field
     * @return $this
     */
    public function text($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' text '];
        return $this;
    }

    /**
     * 设置mediumtext字段
     * @param string $field
     * @return $this
     */
    public function mediumtext($field)
    {
        $this->field = $field;
        $this->instruction[] = ['field' => $field, 'sql' => $field . ' mediumtext '];
        return $this;
    }

    /**
     * 设置字段null
     * @return $this
     */
    public function nullAble()
    {
        $this->instruction[count($this->instruction) - 1]['null'] = true;
        return $this;
    }

    /**
     * 设置字段default
     * @return $this
     */
    public function defaults($value)
    {
        $this->instruction[count($this->instruction) - 1]['default'] = is_string($value) ? "'$value'" : $value;
        return $this;
    }

    /**
     * 设置字段comment
     * @return $this
     */
    public function comment($value)
    {
        $this->instruction[count($this->instruction) - 1]['comment'] = $value;
        return $this;
    }

    /**
     * 设置字段unsigned
     * @return $this
     */
    public function unsigned()
    {
        $this->instruction[count($this->instruction) - 1]['unsigned'] = true;
        return $this;
    }

    /**
     * 删除字段
     * @param $field
     * @return void
     */
    public function dropField($field)
    {
        if (Schema::fieldExists($field, $this->noPreTable)) {
            $sql = sprintf('ALTER TABLE %s DROP %s', $this->table, $field);
            Db::execute($sql);
        }
    }
}
