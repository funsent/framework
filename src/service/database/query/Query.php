<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\query;

use Iterator;
use ArrayAccess;
use funsent\config\Config;
use funsent\paginator\Paginator;
use funsent\filesystem\Filesystem;
use funsent\application\Application;
use funsent\error\exception\RuntimeException;
use funsent\error\exception\InvalidArgumentException;

/**
 * 数据库查询器实现
 * @package funsent\db\query
 */
class Query implements ArrayAccess, Iterator
{
    use ArrayAccessIterator;

    /**
     * 数据
     * @var array
     */
    protected $data = [];

    /**
     * 表名
     * @var string
     */
    protected $table;

    /**
     * 表字段
     * @var array
     */
    protected $fields;

    /**
     * 表主键
     * @var string
     */
    protected $primaryKey;

    /**
     * 数据库连接
     * @var object
     */
    protected $connection;

    /**
     * sql分析实例
     * @var object
     */
    protected $build;

    /**
     * 创建数据库连接对象
     * @return $this
     */
    public function connection()
    {
        $driver = ucfirst(Config::get('db.driver'));
        $connection = '\\funsent\\db\\connection\\' . $driver;
        $this->connection = new $connection($this);
        $build = '\\funsent\\db\\build\\' . $driver;
        $this->build = new $build($this);
        return $this;
    }

    /**
     * 获取表前缀
     * @return string
     */
    protected function getPrefix()
    {
        return Config::get('db.prefix');
    }

    /**
     * 设置表
     * @param string $table 表名
     * @param boolean $full 完整表(含前缀)
     * @return $this
     */
    public function table($table, $full = false)
    {
        // 优先从模型中获取表名，模型实例化后不允许改表名
        $this->table = $this->table ?: ($full ? $table : Config::get('db.prefix') . $table);
        $this->fields = $this->getFields();
        $this->primaryKey = $this->getPrimaryKey();
        return $this;
    }

    /**
     * 获取表
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 过滤表中不存在的字段
     * @param array $data
     * @return array
     */
    public function filterTableField(array $data)
    {
        $info = [];
        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (key_exists($name, $this->fields)) {
                    $info[$name] = $value;
                }
            }
        }
        return $info;
    }

    /**
     * 获取表字段信息
     * @return array|integer|mixed|null
     * @throws \funsent\error\exception\RuntimeException
     */
    public function getFields()
    {
        static $cache = [];
        if (!Config::get('app.debug') && !empty($cache[$this->table])) {
            return $cache[$this->table];
        }
        $isCache = Config::get('db.cache_field');
        $data = ($isCache && !Config::get('app.debug')) ? $this->cache($this->table) : [];
        if (empty($data)) {
            $sql = 'SHOW COLUMNS FROM ' . $this->table;
            if (!$rows = $this->connection->query($sql)) {
                throw new RuntimeException(sprintf('Table field information fetch failure %s', $this->table));
            }
            $data = [];
            foreach ((array)$rows as $row) {
                $field['field'] = $row['Field'];
                $field['type'] = $row['Type'];
                $field['null'] = $row['Null'];
                $field['field'] = $row['Field'];
                $field['key'] = ($row['Key'] == 'PRI' && $row['Extra']) || $row['Key'] == 'PRI';
                $field['default'] = $row['Default'];
                $field['extra'] = $row['Extra'];
                $data[$row['Field']] = $field;
            }
            if ($isCache) {
                $this->cache($this->table, $data);
            }
        }
        $cache[$this->table] = $data;
        return $data;
    }

    /**
     * 获取表主键
     * @return mixed
     */
    public function getPrimaryKey()
    {
        static $cache = [];
        if (isset($cache[$this->table])) {
            return $cache[$this->table];
        }
        $fields = $this->getFields();
        foreach ($fields as $field) {
            if ($field['key'] == 1) {
                return $cache[$this->table] = $field['field'];
            }
        }
    }

    /**
     * 存取缓存表字段
     * @param string $name
     * @param array|null $data
     * @return integer|null
     */
    public function cache($name, $data = null)
    {
        $dir = Config::get('db.cache_dir');
        $dir = Application::runtimePath() . '/' . $dir;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return false;
            }
        }
        Filesystem::createDirectory($dir);
        $file = $dir . '/' . $name . '.php';
        if (is_null($data)) {
            $result = [];
            if (is_file($file)) {
                $result = unserialize(file_get_contents($file));
            }
            return is_array($result) ? $result : [];
        } else {
            return file_put_contents($file, serialize($data));
        }
    }

    /**
     * 设置data数据
     * @param array $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 转数组，获取data数据
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * 插入并获取自增主键
     * @param array $data
     * @param string $action
     * @return boolean|mixed
     */
    public function insertGetId($data, $action = 'insert')
    {
        if ($result = $this->insert($data, $action)) {
            return $this->connection->getInsertId();
        } else {
            return false;
        }
    }

    /**
     * 分页查询
     * @param integer $row 每页显示数量
     * @param integer $pageNum 页码数量
     * @param integer $count 总记录数
     * @return mixed
     */
    public function paginate($row, $pageNum = 8, $count = -1)
    {
        $obj = unserialize(serialize($this));
        $count = is_string($count) ? $obj->count($count) : ($count == -1 ? $obj->count() : $count);
        Paginator::row($row)->pageNum($pageNum)->make($count);
        $res = $this->limit(Paginator::limit())->get();
        $this->data($res ?: []);
        return $this;
    }

    /**
     * 简单分页
     */
    public function simplePaginate()
    {
        return $this;
    }

    /**
     * 获取前台显示页码样式
     * @return mixed
     */
    public function links()
    {
        return Paginator::show();
    }

    /**
     * 执行增删改操作(无结果集的操作)
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function execute($sql, array $params = [])
    {
        $result = $this->connection->execute($sql, $params);
        $this->build->reset();
        return $result;
    }

    /**
     * 执行查询操作(有结果集的操作)
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function query($sql, array $params = [])
    {
        $data = $this->connection->query($sql, $params);
        $this->build->reset();
        return $data;
    }

    /**
     * 增加字段值
     * @param string $field
     * @param integer $num
     * @return mixed
     * @throws \funsent\error\exception\RuntimeException
     */
    public function increment($field, $num = 1)
    {
        $where = $this->build->parseWhere();
        if (empty($where)) {
            throw new RuntimeException(sprintf('Invalid conditions %s()', __METHOD__));
        }
        $sql = 'UPDATE ' . $this->getTable() . " SET {$field} = {$field} + $num " . $where;
        return $this->execute($sql, $this->build->getUpdateParams());
    }

    /**
     * 减少字段值
     * @param string $field
     * @param integer $num
     * @return mixed
     * @throws \funsent\error\exception\RuntimeException
     */
    public function decrement($field, $num = 1)
    {
        $where = $this->build->parseWhere();
        if (empty($where)) {
            throw new RuntimeException(sprintf('Invalid conditions %s()', __METHOD__));
        }
        $sql = 'UPDATE ' . $this->getTable() . " SET {$field} = {$field} - $num " . $where;
        return $this->execute($sql, $this->build->getUpdateParams());
    }

    /**
     * 更新数据
     * @param array $data
     * @return boolean|mixed
     * @throws \funsent\error\exception\RuntimeException
     */
    public function update($data)
    {
        $data = $this->filterTableField($data); // 过滤表中不存在字段
        if (empty($data)) {
            throw new RuntimeException(sprintf('Invalid conditions %s()', __METHOD__));
        }
        foreach ((array)$data as $key => $value) {
            $this->build->bindExpression('set', $key);
            $this->build->bindParams('values', $value);
        }
        if (!$this->build->getBindExpression('where')) {
            // 有主键时使用主键做条件
            $primaryKey = $this->getPrimaryKey();
            if (isset($data[$primaryKey])) {
                $this->where($primaryKey, $data[$primaryKey]);
            }
        }
        if (!$this->build->getBindExpression('where')) {
            throw new RuntimeException(sprintf('Update denied, Invalid conditions %s()', __METHOD__));
        }
        return $this->execute($this->build->update(), $this->build->getUpdateParams());
    }

    /**
     * 删除记录
     * @param array|string $id
     * @return boolean
     */
    public function delete($id = [])
    {
        if (!empty($id)) {
            $this->whereIn($this->getPrimaryKey(), is_array($id) ? $id : explode(',', $id));
        }
        if ($this->build->getBindExpression('where')) { // 必须有条件才可以删除
            return $this->execute($this->build->delete(), $this->build->getDeleteParams());
        }
        return false;
    }

    /**
     * 记录不存在时创建
     * @param array $param
     * @param array $data
     * @return boolean
     */
    function firstOrCreate($param, $data)
    {
        if (!$this->where(key($param), current($param))->first()) {
            return $this->insert($data);
        } else {
            return false;
        }
    }

    /**
     * 插入数据
     * @param array $data
     * @param string $action
     * @return boolean
     * @throws \funsent\error\exception\RuntimeException
     */
    public function insert($data, $action = 'insert')
    {
        // 过滤非法字段
        $data = $this->filterTableField($data);
        if (empty($data)) {
            throw new RuntimeException(sprintf('Data can not be empty %s()', __METHOD__));
        }
        foreach ($data as $key => $value) {
            $this->build->bindExpression('field', "`$key`");
            $this->build->bindExpression('values', '?');
            $this->build->bindParams('values', $value);
        }
        return $this->execute($this->build->$action(), $this->build->getInsertParams());
    }

    /**
     * 替换数据，适用于表中有唯一索引的字段
     * @param array $data
     * @return boolean
     */
    public function replace($data)
    {
        return $this->insert($data, 'replace');
    }

    /**
     * 根据主键查找一条数据
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        if ($id) {
            $this->where($this->getPrimaryKey(), $id);
            if ($data = $this->query($this->build->select(), $this->build->getSelectParams())) {
                return $data ? $data[0] : [];
            }
        }
    }

    /**
     * 查找第一条数据
     * @return array
     */
    public function first()
    {
        $data = $this->query($this->build->select(), $this->build->getSelectParams());
        return $data ? $data[0] : [];
    }

    /**
     * 查询一个字段值
     * @param string $field
     * @return mixed
     */
    public function pluck($field)
    {
        $data = $this->query($this->build->select(), $this->build->getSelectParams());
        $result = $data ? $data[0] : [];
        if (!empty($result)) {
            return $result[$field];
        }
    }

    /**
     * 查询记录集
     * @param array $field
     * @return array
     */
    public function get(array $field = [])
    {
        if (!empty($field)) {
            $this->field($field);
        }
        return $this->query($this->build->select(), $this->build->getSelectParams());
    }

    /**
     * 获取字段列表
     * @param string $field
     * @return array|mixed
     */
    public function lists($field)
    {
        $result = $this->query($this->build->select(), $this->build->getSelectParams());
        $data  = [];
        if ($result) {
            $fields = explode(',', $field);
            $fields = array_map(function($value) { return trim($value); }, $fields); // 去除每个元素值两边的空格
            switch (count($fields)) {
                case 1:
                    foreach ($result as $row) {
                        $data[] = $row[$fields[0]];
                    }
                    break;
                case 2:
                    foreach ($result as $row) {
                        $data[$row[$fields[0]]] = $row[$fields[1]];
                    }
                    break;
                default:
                    foreach ($result as $row) {
                        foreach ($fields as $field) {
                            $data[$row[$fields[0]]][$field] = $row[$field];
                        }
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * 设置记录集字段
     * @param string|array $field 字段列表
     * @return $this
     */
    public function field($field)
    {
        $field = is_array($field) ? $field : explode(',', $field);
        foreach ((array)$field as $key => $value) {
            $this->build->bindExpression('field', $value);
        }
        return $this;
    }

    /**
     * 分组查询
     * @return $this
     */
    public function groupBy()
    {
        $this->build->bindExpression('groupBy', func_get_arg(0));
        return $this;
    }

    /**
     * 分组筛选
     * @return $this
     */
    public function having()
    {
        $args = func_get_args();
        $this->build->bindExpression('having', $args[0] . $args[1] . ' ? ');
        $this->build->bindParams('having', $args[2]);
        return $this;
    }

    /**
     * 排序
     * @return $this
     */
    public function orderBy()
    {
        $args = func_get_args();
        $this->build->bindExpression('orderBy', $args[0] . ' ' . (empty($args[1]) ? 'ASC ' : $args[1]));
        return $this;
    }

    /**
     * 排他锁，结合事务使用
     * @return $this
     */
    public function lock()
    {
        $this->build->bindExpression('lock', ' FOR UPDATE ');
        return $this;
    }

    /**
     * 限制记录数
     * @return $this
     */
    public function limit()
    {
        $args = func_get_args();
        $this->build->bindExpression('limit', $args[0] . (empty($args[1]) ? '' : ',' . $args[1]));
        return $this;
    }

    /**
     * 合计数
     * @param string $field
     * @return integer
     */
    public function count($field = '*')
    {
        $this->build->bindExpression('field', "COUNT($field) AS m");
        $data = $this->first();
        return intval($data ? $data['m'] : 0);
    }

    /**
     * 最大值
     * @param string $field
     * @return integer
     */
    public function max($field)
    {
        $this->build->bindExpression('field', "MAX({$field}) AS m");
        $data = $this->first();
        return intval($data ? $data['m'] : 0);
    }

    /**
     * 最小值
     * @param string $field
     * @return integer
     */
    public function min($field)
    {
        $this->build->bindExpression('field', "MIN({$field}) AS m");
        $data = $this->first();
        return intval($data ? $data['m'] : 0);
    }

    /**
     * 平均数
     * @param string $field
     * @return integer
     */
    public function avg($field)
    {
        $this->build->bindExpression('field', "AVG({$field}) AS m");
        $data = $this->first();
        return intval($data ? $data['m'] : 0);
    }

    /**
     * 求和
     * @param string $field
     * @return integer
     */
    public function sum($field)
    {
        $this->build->bindExpression('field', "SUM({$field}) AS m");
        $data = $this->first();
        return intval($data ? $data['m'] : 0);
    }

    /**
     * 设置逻辑符号
     * @param string $login
     * @return $this
     */
    public function logic($login)
    {
        // 如果上一次设置了 AND 或 OR 语句时忽略
        $expression = $this->build->getBindExpression('where');
        if (empty($expression) || preg_match('/^\s*(OR|AND)\s*$/i', array_pop($expression))) {
            return $this;
        }
        $this->build->bindExpression('where', trim($login));
        return $this;
    }

    /**
     * 设置条件
     * @return $this
     */
    public function where()
    {
        $args = func_get_args();
        if (is_array($args[0])) {
            foreach ($args as $arg) {
                call_user_func_array([$this, 'where'], $arg);
            }
        } else {
            switch (count($args)) {
                case 1:
                    $this->logic('AND')->build->bindExpression('where', $args[0]);
                    break;
                case 2:
                    $this->logic('AND')->build->bindExpression('where', "{$args[0]} = ?");
                    $this->build->bindParams('where', $args[1]);
                    break;
                case 3:
                    $this->logic('AND')->build->bindExpression('where', "{$args[0]} {$args[1]} ?");
                    $this->build->bindParams('where', $args[2]);
                    break;
            }
        }
        return $this;
    }

    /**
     * 设置非空条件
     * @return $this
     */
    public function whereNotEmpty()
    {
        $args = func_get_args();
        if (is_array($args[0])) {
            foreach ($args as $arg) {
                call_user_func_array([$this, 'whereNotEmpty'], $arg);
            }
        } else {
            switch (count($args)) {
                case 1:
                    if (!empty($args[0])) {
                        $this->logic('AND')->build->bindExpression('where', $args[0]);
                    }
                    break;
                case 2:
                    if (!empty($args[1])) {
                        $this->logic('AND')->build->bindExpression('where', "{$args[0]} = ?");
                        $this->build->bindParams('where', $args[1]);
                    }
                    break;
                case 3:
                    if (!empty($args[2])) {
                        $this->logic('AND')->build->bindExpression('where', "{$args[0]} {$args[1]} ?");
                        $this->build->bindParams('where', $args[2]);
                    }
                    break;
            }
        }
        return $this;
    }

    /**
     * 设置预处理语句条件
     * @param string $sql
     * @param array $params
     * @return $this
     */
    public function whereRaw($sql, array $params = [])
    {
        $this->logic('AND');
        $this->build->bindExpression('where', $sql);
        foreach ($params as $p) {
            $this->build->bindParams('where', $p);
        }
        return $this;
    }

    /**
     * 或查询
     * @return $this
     */
    public function orWhere()
    {
        $this->logic('OR');
        call_user_func_array([$this, 'where'], func_get_args());
        return $this;
    }

    /**
     * 与查询
     * @return $this
     */
    public function andWhere()
    {
        $this->build->bindExpression('where', ' AND ');
        call_user_func_array([$this, 'where'], func_get_args());
        return $this;
    }

    /**
     * NULL查询
     * @param string $field
     * @return $this
     */
    public function whereNull($field)
    {
        $this->logic('AND');
        $this->build->bindExpression('where', "$field IS NULL");
        return $this;
    }

    /**
     * NOT NULL查询
     * @param string $field
     * @return $this
     */
    public function whereNotNull($field)
    {
        $this->logic('AND');
        $this->build->bindExpression('where', "$field IS NOT NULL");
        return $this;
    }

    /**
     * IN查询
     * @param string $field
     * @param array $params
     * @return $this
     * @throws \funsent\error\exception\InvalidArgumentException
     */
    public function whereIn($field, $params)
    {
        if (!is_array($params) || empty($params)) {
            throw new InvalidArgumentException(sprintf('Invalid argument %s', __METHOD__));
        }
        $this->logic('AND');
        $where = '';
        foreach ($params as $value) {
            $where .= '?,';
            $this->build->bindParams('where', $value);
        }
        $this->build->bindExpression('where', " $field IN (" . substr($where, 0, -1) . ')');
        return $this;
    }

    /**
     * NOT IN查询
     * @param string $field
     * @param array $params
     * @return $this
     * @throws \funsent\error\exception\InvalidArgumentException
     */
    public function whereNotIn($field, $params)
    {
        if (!is_array($params) || empty($params)) {
            throw new InvalidArgumentException(sprintf('Invalid argument %s', __METHOD__));
        }
        $this->logic('AND');
        $where = '';
        foreach ($params as $value) {
            $where .= '?,';
            $this->build->bindParams('where', $value);
        }
        $this->build->bindExpression('where', " $field NOT IN (" . substr($where, 0, -1) . ')');
        return $this;
    }

    /**
     * BETWEEN查询
     * @param string $field
     * @param array $params
     * @return $this
     * @throws \funsent\error\exception\InvalidArgumentException
     */
    public function whereBetween($field, $params)
    {
        if ( ! is_array($params) || empty($params)) {
            throw new InvalidArgumentException(sprintf('Invalid argument %s', __METHOD__));
        }
        $this->logic('AND');
        $this->build->bindExpression('where', " $field BETWEEN ? AND ? ");
        $this->build->bindParams('where', $params[0]);
        $this->build->bindParams('where', $params[1]);
        return $this;
    }

    /**
     * NOT BETWEEN查询
     * @param string $field
     * @param array $params
     * @return $this
     * @throws \funsent\error\exception\InvalidArgumentException
     */
    public function whereNotBetween($field, $params)
    {
        if ( ! is_array($params) || empty($params)) {
            throw new InvalidArgumentException(sprintf('Invalid argument %s', __METHOD__));
        }
        $this->logic('AND');
        $this->build->bindExpression('where', " $field NOT BETWEEN ? AND ? ");
        $this->build->bindParams('where', $params[0]);
        $this->build->bindParams('where', $params[1]);
        return $this;
    }

    /**
     * 多表内连接
     * @return $this
     */
    public function join()
    {
        $args = func_get_args();
        $this->build->bindExpression('join', ' INNER JOIN ' . $this->getPrefix() . "{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
        return $this;
    }

    /**
     * 多表左外连接
     * @return $this
     */
    public function leftJoin()
    {
        $args = func_get_args();
        $this->build->bindExpression('join', ' LEFT JOIN ' . $this->getPrefix() . "{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
        return $this;
    }

    /**
     * 多表右外连接
     * @return $this
     */
    public function rightJoin()
    {
        $args = func_get_args();
        $this->build->bindExpression('join', ' RIGHT JOIN ' . $this->getPrefix() . "{$args[0]} {$args[0]} ON {$args[1]} {$args[2]} {$args[3]}");
        return $this;
    }

    /**
     * 调用不存在的实例方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (substr($method, 0, 5) == 'getBy') { // 根据字段名获取字段值
            $field = preg_replace('/.[A-Z]/', '_\1', substr($method, 5));
            $field = strtolower($field);
            return $this->where($field, current($parameters))->first();
        }
        return call_user_func_array([$this->connection, $method], $parameters);
    }

    /**
     * 获取查询参数
     * @param string $type where field等
     * @return mixed
     */
    public function getQueryParams($type)
    {
        return $this->build->getBindExpression($type);
    }
}
