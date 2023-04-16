<?php

/*
 * Copyright(C) 2017 funsent.com Inc. All Rights Reserved.
 * This is NOT a freeware, use is subject to license terms.
 * $Id$
 */

namespace funsent\db\driver;

use PDO;
use funsent\db\contract\AbstractDb;

/**
 * pdo_sqlsrv 扩展驱动的数据库处理类
 */
class Sqlsrv extends AbstractDb
{
    // PDO连接参数
    protected $option = array(
        PDO::ATTR_CASE              => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING   => PDO::SQLSRV_ENCODING_UTF8,
        PDO::ATTR_STRINGIFY_FETCHES => false
    );

    // 查询sql模板字符串
    protected $selectSql = 'SELECT T1.* FROM (SELECT funsent.*, ROW_NUMBER() OVER (%ORDER%) AS ROW_NUMBER FROM (SELECT %DISTINCT% %FIELD% FROM %TABLE% %JOIN% %WHERE% %GROUP% %HAVING% %UNION%) AS funsent) AS T1 %LIMIT% %COMMENT%';

    /**
     * 重写父类方法，解析 pdo连接的dsn信息
     * 
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'sqlsrv:Database=' . $config['name'] . ';Server=' . $config['host'];
        if (!empty($config['port'])) {
            $dsn .= ',' . $config['port'];
        }
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * 
     * @param string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $result = $this->query("SELECT column_name,data_type,column_default,is_nullable FROM information_schema.tables AS t JOIN information_schema.columns AS c ON  t.table_catalog = c.table_catalog AND t.table_schema = c.table_schema AND t.table_name = c.table_name WHERE t.table_name = '{$tableName}'");
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['column_name']] = array(
                    'name' => $val['column_name'],
                    'type' => $val['data_type'],
                    'notnull' => (bool) ($val['is_nullable'] === ''), // not null is empty, null is yes
                    'default' => $val['column_default'],
                    'primary' => false,
                    'autoinc' => false
                );
            }
        }
        return $info;
    }
    
    /**
     * 取得数据库的表信息
     * 
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query("SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE'");
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 重写父类方法，解析 order
     * 
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        return !empty($order) ? ' ORDER BY ' . $order : ' ORDER BY rand()';
    }

    /**
     * 重写父类方法，解析 字段名和表名
     * 
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)\[.\s]/', $key)) {
            $key = '[' . $key . ']';
        }
        return $key;
    }
    
    /**
     * 重写父类方法，解析 limit
     * 
     * @param mixed $limit
     * @return string
     */
    public function parseLimit($limit)
    {
        if (empty($limit)) {
            return '';
        }
        $limit = explode(',', $limit);
        if (count($limit) > 1) {
            $limitStr = '(T1.ROW_NUMBER BETWEEN ' . $limit[0] . ' + 1 AND ' . $limit[0] . ' + ' . $limit[1] . ')';
        } else {
            $limitStr = '(T1.ROW_NUMBER BETWEEN 1 AND ' . $limit[0] . ")";
        }
        return 'WHERE ' . $limitStr;
    }

    /**
     * 重写父类方法，更新记录
     * 
     * @param mixed $data
     * @param array $option
     * @return mixed
     */
    public function update($data, $option)
    {
        $this->modelName = $option['model'];
        $this->parseBind(!empty($option['bind']) ? $option['bind'] : array());
        $sql = 'UPDATE '
            . $this->parseTable($option['table'])
            . $this->parseSet($data)
            . $this->parseWhere(!empty($option['where']) ? $option['where'] : '')
            . $this->parseLock(isset($option['lock']) ? $option['lock'] : false)
            . $this->parseComment(!empty($option['comment']) ? $option['comment'] : '');
        return $this->execute($sql, !empty($option['fetch_sql']) ? true : false);
    }
    
    /**
     * 重写父类方法，删除记录
     * 
     * @param array $option
     * @return mixed
     */
    public function delete($option = array())
    {
        $this->modelName = $option['model'];
        $this->parseBind(!empty($option['bind']) ? $option['bind'] : array());
        $sql = 'DELETE FROM '
            . $this->parseTable($option['table'])
            . $this->parseWhere(!empty($option['where']) ? $option['where'] : '')
            . $this->parseLock(isset($option['lock']) ? $option['lock'] : false)
            . $this->parseComment(!empty($option['comment']) ? $option['comment'] : '');
        return $this->execute($sql, !empty($option['fetch_sql']) ? true : false);
    }
}
