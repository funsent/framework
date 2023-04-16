<?php

/*
 * Copyright(C) 2017 funsent.com Inc. All Rights Reserved.
 * This is NOT a freeware, use is subject to license terms.
 * $Id$
 */

namespace funsent\db\driver;

use funsent\Config;
use funsent\db\contract\AbstractDb;

/**
 * pdo_oracle 扩展驱动的数据库处理类
 * @package funsent\db\driver
 */
class Oracle extends AbstractDb
{
    // 数据表
    private $_table = '';

    // 查询sql模板字符串
    protected $selectSql = 'SELECT * FROM (SELECT funsent.*, rownum AS numrow FROM (SELECT %DISTINCT% %FIELD% FROM %TABLE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER%) funsent) %LIMIT% %COMMENT%';

    /**
     * 重写父类方法，解析 pdo连接的dsn信息
     *
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'oci:dbname=//' . $config['host'] . ($config['port'] ? ':' . $config['port'] : '') . '/' . $config['name'];
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }

    /**
     * 重写父类方法，sql执行
     *
     * @param string $str sql指令
     * @param boolean $fetchSql 不执行只是获取sql
     * @return integer
     */
    public function execute($str, $fetchSql = false)
    {
        $this->initConnect(true);
        if (!$this->link) {
            return false;
        }
        $this->sql = $str;
        if (!empty($this->params)) {
            $that = $this;
            $this->sql = strtr($this->sql, array_map(function($val) use ($that) {
                return '\'' . $that->escapeString($val) . '\'';
            }, $this->params));
        }
        if ($fetchSql) {
            return $this->sql;
        }
        $flag = false;
        if (preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $str, $match)) {
            $this->_table = Config::get('db.sequence_prefix') . str_ireplace($this->config['table_prefix'], '', $match[2]);
            $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->_table) . "'");
        }
        // 释放前次的查询结果
        if (!empty($this->statement)) {
            $this->free();
        }
        $this->executeTimes++;
        // 调试开始
        $this->debug(true);
        $this->statement = $this->link->prepare($str);
        if (false === $this->statement) {
            $this->error();
            return false;
        }
        foreach ($this->params as $key => $val) {
            if (is_array($val)) {
                $this->statement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->statement->bindValue($key, $val);
            }
        }
        $this->params = array();
        $result = $this->statement->execute();
        // 调试结束
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->statement->rowCount();
            if ($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsertId = $this->link->lastInsertId();
            }
            return $this->numRows;
        }
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
        $result = $this->query("SELECT a.column_name,data_type,decode(nullable,'Y',0,1) notnull,data_default,decode(a.column_name,b.column_name,1,0) pk "
            . "FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
            . "WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' AND c.table_name='"
            . strtoupper($tableName) . "') b WHERE table_name='"
            . strtoupper($tableName) . "' AND a.column_name=b.column_name(+)"
        );
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[strtolower($val['column_name'])] = array(
                    'name' => strtolower($val['column_name']),
                    'type' => strtolower($val['data_type']),
                    'notnull' => $val['notnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk']
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息（暂时实现取得用户表信息）
     *
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query('SELECT table_name FROM user_tables');
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 重写父类方法，sql安全过滤
     *
     * @param string $str sql指令
     * @return string
     */
    public function escapeString($str)
    {
        return str_ireplace("'", "''", $str);
    }

    /**
     * 重写父类方法，解析 limit
     *
     * @param mixed $limit
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = '(numrow>' . $limit[0] . ') AND (numrow<=' . ($limit[0] + $limit[1]) . ')';
            } else {
                $limitStr = '(numrow>0 AND numrow<=' . $limit[0] . ')';
            }
        }
        return $limitStr ? ' WHERE ' . $limitStr : '';
    }

    /**
     * 重写父类方法，解析 lock
     *
     * @param boolean $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        return $lock ? ' FOR UPDATE NOWAIT ' : '';
    }
}
