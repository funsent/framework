<?php

/*
 * Copyright(C) 2017 funsent.com Inc. All Rights Reserved.
 * This is NOT a freeware, use is subject to license terms.
 * $Id$
 */

namespace funsent\db\driver;

use funsent\lang\Lang;
use funsent\db\contract\AbstractDb;
use funsent\error\exception\Exception;
use funsent\error\exception\ExtensionNotFoundException;

/**
 * pdo_mongo 扩展驱动的数据库处理类
 */
class Mongo extends AbstractDb
{
    // MongoDb 实例
    protected $_mongo = null;

    // MongoCollection 实例
    protected $_collection = null;

    // 游标实例
    protected $_cursor = null;

    // 数据库名
    protected $_dbName = '';

    // collection 名
    protected $_collectionName = '';

    // 表达式
    protected $comparison = array(
        'neq'    => 'ne',
        'ne'     => 'ne',
        'gt'     => 'gt',
        'egt'    => 'gte',
        'gte'    => 'gte',
        'lt'     => 'lt',
        'elt'    => 'lte',
        'lte'    => 'lte',
        'in'     => 'in',
        'not in' => 'nin',
        'nin'    => 'nin'
    );
    
    /**
     * 重写父类方法，解析数据库配置
     * 
     * @param array $config
     * @return void
	 * @throws \funsent\error\exception\ExtensionNotFoundException
     */
    public function __construct($config = '')
    {
        if (!class_exists('mongoClient')) {
            throw new ExtensionNotFoundException('Extension not found Mongo');
        }
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
            if (empty($this->config['params'])) {
                $this->config['params'] = array();
            }
        }
    }
    
    /**
     * 重写父类方法，连接数据库方法
     * 
     * @param mixed $config
     * @param integer $linkNum
     * @return resource
	 * @throws \MongoConnectionException
	 * @throws \funsent\error\exception\Exception
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset($this->links[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }
            $host = 'mongodb://' . ($config['username'] ? "{$config['username']}" : '') . ($config['password'] ? ":{$config['password']}@" : '') . $config['host'] . ($config['port'] ? ":{$config['port']}" : '') . '/' . ($config['name'] ? "{$config['name']}" : '');
            try {
                $this->links[$linkNum] = new \MongoClient($host, $this->config['params']);
            } catch (\MongoConnectionException $e) {
                throw new Exception($e->getMessage());
            }
        }
        return $this->links[$linkNum];
    }
    
    /**
     * 切换当前操作的 Db 和 Collection
     * 
     * @param string $collection
     * @param string $db
     * @param boolean $master 是否主服务器
     * @return void
	 * @throws \MongoException
	 * @throws \funsent\error\exception\Exception
     */
    public function switchCollection($collection, $db = '', $master = true)
    {
        // 当前没有连接 则首先进行数据库连接
        if (!$this->link) {
            $this->initConnect($master);
        }
        try {
            if (!empty($db)) { // 传人 Db 则切换数据库
                // 当前 MongoDb 对象
                $this->_dbName = $db;
                $this->_mongo = $this->link->selectDb($db);
            }
            // 当前 MongoCollection 对象
            if ($this->config['debug']) {
                $this->sql = $this->_dbName . '.getCollection(' . $collection . ')';
            }
            if ($this->_collectionName != $collection) {
                $this->queryTimes++;
                // 调试开始
                $this->debug(true);
                $this->_collection = $this->_mongo->selectCollection($collection);
                // 调试结束
                $this->debug(false);
                $this->_collectionName = $collection; // 记录当前 Collection 名称
            }
        } catch (\MongoException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，释放查询结果
     * 
     * @return void
     */
    public function free()
    {
        $this->_cursor = null;
    }
    
    /**
     * 执行命令
     * 
     * @param array $command 指令
     * @param array $option
     * @return array
	 * @throws \MongoCursorException
	 * @throws Exception
     */
    public function command($command = array(), $option = array())
    {
        $cache = isset($option['cache']) ? $option['cache'] : false;
        if ($cache) { // 查询缓存检测
            $key = is_string($cache['key']) ? $cache['key'] : md5(serialize($command));
            $value = cache($key, '', array('driver' => $cache['driver']));
            if (false !== $value) {
                return $value;
            }
        }
        $this->executeTimes++;
        try {
            if ($this->config['debug']) {
                $this->sql = $this->_dbName . '.' . $this->_collectionName . '.runCommand(';
                $this->sql .= json_encode($command);
                $this->sql .= ')';
            }
            // 调试开始
            $this->debug(true);
            $result = $this->_mongo->command($command);
            // 调试结束
            $this->debug(false);
            if ($cache && $result['ok']) { // 查询缓存写入
                cache($key, $result, array('driver' => $cache['driver'], 'lifetime' => $cache['lifetime']));
            }
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，sql执行
     * 
     * @param string $code sql指令
     * @param array $args 参数
     * @return mixed
	 * @throws \funsent\error\exception\Exception
     */
    public function execute($code, $args = array())
    {
        $this->executeTimes++;
        // 调试开始
        $this->debug(true);
        $this->sql = 'execute:' . $code;
        $result = $this->_mongo->execute($code, $args);
        // 调试结束
        $this->debug(false);
        if ($result['ok']) {
            return $result['retval'];
        } else {
            throw new Exception($result['errmsg']);
        }
    }
    
    /**
     * 重写父类方法，关闭数据库
     * 
     * @return void
     */
    public function close()
    {
        if ($this->link) {
            $this->link->close();
            $this->link = null;
            $this->_mongo = null;
            $this->_collection = null;
            $this->_cursor = null;
        }
    }
    
    /**
     * 重写父类方法，数据库错误信息，显示当前的sql语句
     * 
     * @return string
     */
    public function error()
    {
        $this->error = $this->_mongo->lastError();
        //trace($this->error, '', 'ERR');
        return $this->error;
    }
    
    /**
     * 重写父类方法，插入记录
     * 
     * @param mixed $data 数据
     * @param array $option
     * @param boolean $replace
     * @return mixed
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function insert($data, $option = array(), $replace = false)
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table']);
        }
        $this->modelName = $option['model'];
        $this->executeTimes++;      
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.insert(';
            $this->sql .= $data ? json_encode($data) : '{}';
            $this->sql .= ')';
        }
        try {
            // 调试开始
            $this->debug(true);
            $result = $replace ? $this->_collection->save($data) : $this->_collection->insert($data);
            // 调试结束
            $this->debug(false);
            if ($result) {
                $_id = $data['_id'];
                if (is_object($_id)) {
                    $_id = $_id->__toString();
                }
                $this->lastInsertId = $_id;
            }
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，批量插入记录
     * 
     * @param array $dataList 数据
     * @param array $option
     * @return boolean
	 * @throws \funsent\error\exception\MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function insertAll($dataList, $option = array())
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table']);
        }
        $this->modelName = $option['model'];
        $this->executeTimes++;      
        try {
            // 调试开始
            $this->debug(true);
            $result = $this->_collection->batchInsert($dataList);
            // 调试结束
            $this->debug(false);
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 生成下一条记录ID 用于自增非MongoId主键
     * 
     * @param string $pk 主键名
     * @return integer
	 * @throws \funsent\error\exception\MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function getMongoNextId($pk)
    {
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.find({},{' . $pk . ':1}).sort({' . $pk . ':-1}).limit(1)';
        }
        try {
            // 调试开始
            $this->debug(true);
            $result = $this->_collection->find(array(), array($pk => 1))->sort(array($pk => -1))->limit(1);
            // 调试结束
            $this->debug(false);
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
        $data = $result->getNext();
        return isset($data[$pk]) ? $data[$pk] + 1 : 1;
    }
    
    /**
     * 重写父类方法，更新记录
     * 
     * @param mixed $data
     * @param array $option
     * @return boolean
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function update($data, $option)
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table']);
        }
        $this->executeTimes++;      
        $this->modelName = $option['model'];
        $query = $this->parseWhere(isset($option['where']) ? $option['where'] : array());
        $set = $this->parseSet($data);
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.update(';
            $this->sql .= $query ? json_encode($query) : '{}';
            $this->sql .= ',' . json_encode($set) . ')';
        }
        try {
            // 调试开始
            $this->debug(true);
            if (isset($option['limit']) && $option['limit'] == 1) {
                $multiple = array('multiple' => false);
            } else {
                $multiple = array('multiple' => true);
            }
            $result = $this->_collection->update($query, $set, $multiple);
            // 调试结束
            $this->debug(false);
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，删除记录
     * 
     * @param array $option
     * @return mixed
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function delete($option = array())
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table']);
        }
        $query = $this->parseWhere(isset($option['where']) ? $option['where'] : array());
        $this->modelName = $option['model'];
        $this->executeTimes++;     
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.remove(' . json_encode($query) . ')';
        }
        try {
            // 调试开始
            $this->debug(true);
            $result = $this->_collection->remove($query);
            // 调试结束
            $this->debug(false);
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 清空记录
     * 
     * @param array $option
     * @return mixed
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function clear($option = array())
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table']);
        }
        $this->modelName = $option['model'];
        $this->executeTimes++;     
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.remove({})';
        }
        try {
            // 调试开始
            $this->debug(true);
            $result = $this->_collection->drop();
            // 调试结束
            $this->debug(false);
            return $result;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，查找记录
     * 
     * @param array $option
     * @return \iterator
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function select($option = array())
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table'], '', false);
        }
        $this->modelName = $option['model'];
        $this->queryTimes++;     
        $query = $this->parseWhere(isset($option['where']) ? $option['where'] : array());
        $field = $this->parseField(isset($option['field']) ? $option['field'] : array());
        try {
            if ($this->config['debug']) {
                $this->sql = $this->_dbName . '.' . $this->_collectionName . '.find(';
                $this->sql .= $query ? json_encode($query) : '{}';
                if (is_array($field) && count($field)) {
                    foreach ($field as $f => $v) {
                        $_field_array[$f] = $v ? 1 : 0;
                    }
                    $this->sql .= $field ? ', ' . json_encode($_field_array) : ', {}';
                }
                $this->sql .= ')';
            }
            // 调试开始
            $this->debug(true);
            $_cursor = $this->_collection->find($query, $field);
            if (!empty($option['order'])) {
                $order = $this->parseOrder($option['order']);
                if ($this->config['debug']) {
                    $this->sql .= '.sort(' . json_encode($order) . ')';
                }
                $_cursor = $_cursor->sort($order);
            }
            if (isset($option['page'])) { // 根据页数计算limit
                list($page, $length) = $option['page'];
                $page = $page > 0 ? $page : 1;
                $length = $length > 0 ? $length : (is_numeric($option['limit']) ? $option['limit'] : 20);
                $offset = $length * ((int)$page - 1);
                $option['limit'] = $offset . ',' . $length;
            }
            if (isset($option['limit'])) {
                list($offset, $length) = $this->parseLimit($option['limit']);
                if (!empty($offset)) {
                    if ($this->config['debug']) {
                        $this->sql .= '.skip(' . intval($offset) . ')';
                    }
                    $_cursor = $_cursor->skip(intval($offset));
                }
                if ($this->config['debug']) {
                    $this->sql .= '.limit(' . intval($length) . ')';
                }
                $_cursor = $_cursor->limit(intval($length));
            }
            // 调试结束
            $this->debug(false);
            $this->_cursor = $_cursor;
            $resultSet = iterator_to_array($_cursor);
            return $resultSet;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 重写父类方法，查找某个记录
     * 
     * @param array $option
     * @return array
     */
    public function find($option = array())
    {
        $option['limit'] = 1;
        $find = $this->select($option);
        return array_shift($find);
    }
    
    /**
     * 统计记录数
     * 
     * @param array $option
     * @return \iterator
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function count($option = array())
    {
        if (isset($option['table'])) {
            $this->switchCollection($option['table'], '', false);
        }
        $this->modelName = $option['model'];
        $this->queryTimes++;      
        $query = $this->parseWhere(isset($option['where']) ? $option['where'] : array());
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName;
            $this->sql .= $query ? '.find(' . json_encode($query) . ')' : '';
            $this->sql .= '.count()';
        }
        try {
            // 调试开始
            $this->debug(true);
            $count = $this->_collection->count($query);
            // 调试结束
            $this->debug(false);
            return $count;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 分组记录
     * 
     * @param mixed $keys
     * @param mixed $initial
     * @param mixed $reduce
     * @param array $option
     * @return array
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function group($keys, $initial, $reduce, $option = array())
    {
        if (isset($option['table']) && $this->_collectionName != $option['table']) {
            $this->switchCollection($option['table'], '', false);
        }
        $cache = isset($option['cache']) ? $option['cache'] : false;
        if ($cache) {
            $key = is_string($cache['key']) ? $cache['key'] : md5(serialize($option));
            $value = cache($key, '', array('driver' => $cache['driver']));
            if (false !== $value) {
                return $value;
            }
        }
        $this->modelName = $option['model'];
        $this->queryTimes++;       
        $query = $this->parseWhere(isset($option['where']) ? $option['where'] : array());
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.group({key:' . json_encode($keys) . ',cond:' . json_encode($option['condition']) . ',reduce:' . json_encode($reduce) . ',initial:' . json_encode($initial) . '})';
        }
        try {
            // 调试开始
            $this->debug(true);
            $options = array(
                'condition' => $option['condition'],
                'finalize' => $option['finalize'],
                'maxTimeMS' => $option['maxTimeMS']
            );
            $group = $this->_collection->group($keys, $initial, $reduce, $options);
            // 调试结束
            $this->debug(false);
            if ($cache && $group['ok']) {
                cache($key, $group, array('driver' => $cache['driver'], 'lifetime' => $cache['lifetime']));
            }
            return $group;
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * 取得数据表的字段信息
     * 
     * @param string $collection
     * @return array|boolean
	 * @throws \MongoCursorException
	 * @throws \funsent\error\exception\Exception
     */
    public function getFields($collection = '')
    {
        if (!empty($collection) && $collection != $this->_collectionName) {
            $this->switchCollection($collection, '', false);
        }
        $this->queryTimes++;     
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.' . $this->_collectionName . '.findOne()';
        }
        try {
            // 调试开始
            $this->debug(true);
            $result = $this->_collection->findOne();
            // 调试结束
            $this->debug(false);
        } catch (\MongoCursorException $e) {
            throw new Exception($e->getMessage());
        }
        if ($result) { // 存在数据则分析字段
            $info = array();
            foreach ($result as $key => $val) {
                $info[$key] = array(
                    'name' => $key,
                    'type' => getType($val)
                );
            }
            return $info;
        }
        // 暂时没有数据 返回false
        return false;
    }
    
    /**
     * 取得当前数据库的 collection 信息
     * 
     * @return array
     */
    public function getTables()
    {
        if ($this->config['debug']) {
            $this->sql = $this->_dbName . '.getCollenctionNames()';
        }
        $this->queryTimes++;
        // 调试开始       
        $this->debug(true);
        $list = $this->_mongo->listCollections();
        // 调试结束
        $this->debug(false);
        $info = array();
        foreach ($list as $collection) {
            $info[] = $collection->getName();
        }
        return $info;
    }
    
    /**
     * 取得当前数据库的对象
     * 
     * @return \MongoClient
     */
    public function getDB()
    {
        return $this->_mongo;
    }
    
    /**
     * 取得当前集合的对象
     * 
     * @return \MongoCollection
     */
    public function getCollection()
    {
        return $this->_collection;
    }
    
    /**
     * 重写父类方法，解析 set
     * 
     * @param array $data
     * @return array
     */
    protected function parseSet($data)
    {
        $result = array();
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                switch ($val[0]) {
                    case 'inc':
                        $result['$inc'][$key] = (int)$val[1];
                        break;
                    case 'set':
                    case 'unset':
                    case 'push':
                    case 'pushall':
                    case 'addtoset':
                    case 'pop':
                    case 'pull':
                    case 'pullall':
                        $result['$' . $val[0]][$key] = $val[1];
                        break;
                    default:
                        $result['$set'][$key] = $val;
                }
            } else {
                $result['$set'][$key] = $val;
            }
        }
        return $result;
    }
    
    /**
     * 重写父类方法，解析 order
     * 
     * @param mixed $order
     * @return array
     */
    protected function parseOrder($order)
    {
        if (is_string($order)) {
            $array = explode(',', $order);
            $order = array();
            foreach ($array as $key => $val) {
                $arr = explode(' ', trim($val));
                if (isset($arr[1])) {
                    $arr[1] = $arr[1] == 'asc' ? 1 : -1;
                } else {
                    $arr[1] = 1;
                }
                $order[$arr[0]] = $arr[1];
            }
        }
        return $order;
    }
    
    /**
     * 重写父类方法，解析 limit
     * 
     * @param mixed $limit
     * @return array
     */
    protected function parseLimit($limit)
    {
        if (strpos($limit, ',')) {
            $array = explode(',', $limit);
        } else {
            $array = array(0, $limit);
        }
        return $array;
    }
    
    /**
     * 重写父类方法，解析 field
     * 
     * @param mixed $fields
     * @return array
     */
    public function parseField($fields)
    {
        if (empty($fields)) {
            $fields = array();
        }
        if (is_string($fields)) {
            $_fields = explode(',', $fields);
            $fields = array();
            foreach ($_fields as $f) {
                $fields[$f] = true;
            }
        } elseif (is_array($fields)) {
            $_fields = $fields;
            $fields = array();
            foreach ($_fields as $f => $v) {
                if (is_numeric($f)) {
                    $fields[$v] = true;
                } else {
                    $fields[$f] = $v ? true : false;
                }
            }
        }
        return $fields;
    }
    
    /**
     * 重写父类方法，解析 where
     * @param mixed $where
     * @return array
     * @throws \funsent\error\exception\Exception
     */
    public function parseWhere($where)
    {
        $query = array();
        $return = array();
        $_logic = '$and';
        if (isset($where['_logic'])) {
            $where['_logic'] = strtolower($where['_logic']);
            $_logic = in_array($where['_logic'], array('or', 'xor', 'nor', 'and')) ? '$' . $where['_logic'] : $_logic;
            unset($where['_logic']);
        }
        foreach ($where as $key => $val) {
            if ('_id' != $key && 0 === strpos($key, '_')) {
                // 解析特殊条件表达式
                $parse = $this->parseFunsentWhere($key, $val);
                $query = array_merge($query, $parse);
            } else {
                // 查询字段的安全过滤
                if (!preg_match('/^[A-Z_\|\&\-.a-z0-9]+$/', trim($key))) {
                    throw new Exception(Lang::get('QUERY_ERROR') . ': ' . $key);
                }
                $key = trim($key);
                if (strpos($key, '|')) {
                    $array = explode('|', $key);
                    $str = array();
                    foreach ($array as $k) {
                        $str[] = $this->parseWhereItem($k, $val);
                    }
                    $query['$or'] = $str;
                } elseif (strpos($key, '&')) {
                    $array = explode('&', $key);
                    $str = array();
                    foreach ($array as $k) {
                        $str[] = $this->parseWhereItem($k, $val);
                    }
                    $query = array_merge($query, $str);
                } else {
                    $str = $this->parseWhereItem($key, $val);
                    $query = array_merge($query, $str);
                }
            }
        }
        if ($_logic == '$and') {
            return $query;
        }
        foreach ($query as $key => $val) {
            $return[$_logic][] = array($key => $val);
        }
        return $return;
    }
    
    /**
     * 重写父类方法，解析 特殊where
     * 
     * @param string $key
     * @param mixed $val
     * @return array
     */
    protected function parseFunsentWhere($key, $val)
    {
        $query = array();
        $_logic = array('or', 'xor', 'nor', 'and');
        switch ($key) {
            case '_query': // 字符串模式查询条件
                parse_str($val, $query);
                if (isset($query['_logic']) && strtolower($query['_logic']) == 'or') {
                    unset($query['_logic']);
                    $query['$or'] = $query;
                }
                break;
            case '_complex': // 子查询模式查询条件
                $__logic = strtolower($val['_logic']);
                if (isset($val['_logic']) && in_array($__logic, $_logic)) {
                    unset($val['_logic']);
                    $query['$' . $__logic] = $val;
                }
                break;
            case '_string': // MongoCode 查询
                $query['$where'] = new \MongoCode($val);
                break;
        }
        // 兼容 MongoClient OR 条件查询方法
        if (isset($query['$or']) && !is_array(current($query['$or']))) {
            $val = array();
            foreach ($query['$or'] as $k => $v) {
                $val[] = array($k => $v);
            }
            $query['$or'] = $val;
        }
        return $query;
    }
    
    /**
     * 重写父类方法，解析 where子项
     * 
     * @param string $key
     * @param mixed $val
     * @return array
     */
    protected function parseWhereItem($key, $val)
    {
        $query = array();
        if (is_array($val)) {
            if (is_string($val[0])) {
                $con = strtolower($val[0]);
                if (in_array($con, array('neq', 'ne', 'gt', 'egt', 'gte', 'lt', 'lte', 'elt'))) { // 比较运算
                    $k = '$' . $this->comparison[$con];
                    $query[$key] = array($k => $val[1]);
                } elseif ('like' == $con) { // 模糊查询 采用正则方式
                    $query[$key] = new \MongoRegex("/" . $val[1] . "/");
                } elseif ('mod' == $con) { // mod 查询
                    $query[$key] = array('$mod' => $val[1]);
                } elseif ('regex' == $con) { // 正则查询
                    $query[$key] = new \MongoRegex($val[1]);
                } elseif (in_array($con, array('in', 'nin', 'not in'))) { // in nin 运算
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $k = '$' . $this->comparison[$con];
                    $query[$key] = array($k => $data);
                } elseif ('all' == $con) { // 满足所有指定条件
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $query[$key] = array('$all' => $data);
                } elseif ('between' == $con) { // BETWEEN运算
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $query[$key] = array('$gte' => $data[0], '$lte' => $data[1]);
                } elseif ('not between' == $con) {
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $query[$key] = array('$lt' => $data[0], '$gt' => $data[1]);
                } elseif ('exp' == $con) { // 表达式查询
                    $query['$where'] = new \MongoCode($val[1]);
                } elseif ('exists' == $con) { // 字段是否存在
                    $query[$key] = array('$exists' => (bool)$val[1]);
                } elseif ('size' == $con) { // 限制属性大小
                    $query[$key] = array('$size' => intval($val[1]));
                } elseif ('type' == $con) { // 限制字段类型 1 浮点型 2 字符型 3 对象或者MongoDBRef 5 MongoBinData 7 MongoId 8 布尔型 9 MongoDate 10 NULL 15 MongoCode 16 32位整型 17 MongoTimestamp 18 MongoInt64 如果是数组的话判断元素的类型
                    $query[$key] = array('$type' => intval($val[1]));
                } else {
                    $query[$key] = $val;
                }
                return $query;
            }
        }
        $query[$key] = $val;
        return $query;
    }
}
