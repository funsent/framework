<?php

/*
 * Copyright(C) 2017 funsent.com Inc. All Rights Reserved.
 * This is NOT a freeware, use is subject to license terms.
 * $Id$
 */

namespace funsent\db\driver;

use funsent\db\contract\AbstractDb;

/**
 * pdo_pgsql 扩展驱动的数据库处理类
 * @package funsent\db\driver
 */
class Pgsql extends AbstractDb
{
    /**
     * 重写父类方法，解析 pdo连接的dsn信息
     * 
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'pgsql:dbname=' . $config['name'] . ';host=' . $config['host'];
        if (!empty($config['port'])) {
            $dsn .= ';port=' . $config['port'];
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
        $result = $this->query('SELECT fields_name AS "field",fields_type AS "type",fields_not_null AS "null",fields_key_name AS "key",fields_default AS "default",fields_default AS "extra" FROM table_msg(' . $tableName . ');');
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool) ($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
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
        $result = $this->query("SELECT tablename AS Tables_in_test FROM pg_tables WHERE schemaname ='public'");
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
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
