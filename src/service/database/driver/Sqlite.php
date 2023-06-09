<?php

/*
 * Copyright(C) 2017 funsent.com Inc. All Rights Reserved.
 * This is NOT a freeware, use is subject to license terms.
 * $Id$
 */

namespace funsent\db\driver;

use funsent\db\contract\AbstractDb;

/**
 * pdo_sqlite 扩展驱动的数据库处理类
 * @package funsent\db\driver
 */
class Sqlite extends AbstractDb
{
    /**
     * 重写父类方法，解析 pdo连接的dsn信息
     * 
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'sqlite:' . $config['name'];
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
        $result = $this->query('PRAGMA table_info( ' . $tableName . ' )');
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool) ($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['dey']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment')
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
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type='table' ORDER BY name");
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
                $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
            } else {
                $limitStr .= ' LIMIT ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }
}
