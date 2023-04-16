<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\schema\driver;

use funsent\db\Db;
use funsent\config\Config;

/**
 * Mysql驱动的模式服务实现
 * @package funsent\schema\driver
 */
class Mysql
{
    /**
     * 获取表字段信息
     * @param string $table
     * @return array
     */
    public function getFields($table)
    {
        $sql = sprintf('SHOW COLUMNS FROM `%s%s`', Config::get('db.prefix'), $table);
        if (!$rows = Db::query($sql)) {
            return [];
        }
        $info = [];
        foreach ((array)$rows as $row) {
            $field['field'] = $row['Field'];
            $field['type'] = $row['Type'];
            $field['null'] = $row['Null'];
            $field['field'] = $row['Field'];
            $field['key'] = ($row['Key'] == 'PRI' && $row['Extra']) || $row['Key'] == 'PRI';
            $field['default'] = $row['Default'];
            $field['extra'] = $row['Extra'];
            $info[$row['Field']] = $field;
        }
        return $info;
    }

    /**
     * 获取表主键
     * @param string $table
     * @return mixed
     */
    public function getPrimaryKey($table)
    {
        $fields = $this->getFields($table);
        foreach ($fields as $field) {
            if ($field['key'] == 1) {
                return $field['field'];
            }
        }
    }

    /**
     * 删除表
     * @param string $table
     * @return mixed
     */
    public function drop($table)
    {
        if ($this->tableExists($table)) {
            $sql = sprintf('DROP TABLE `%s%s`', Config::get('db.prefix'), $table);
            return Db::execute($sql);
        }
        return false;
    }

    /**
     * 删除表字段
     * @param string $table 表名
     * @param string $field 字段名
     * @return boolean
     */
    public function dropField($table, $field)
    {
        if ($this->fieldExists($field, $table)) {
            $sql = sprintf('ALTER TABLE `%s%s` DROP COLUMN `%s`', Config::get('db.prefix'), $table, $field);
            return Db::execute($sql);
        }
        return true;
    }

    /**
     * 修复表
     * @param string $table
     * @return mixed
     */
    public function repair($table)
    {
        $sql = sprintf('REPAIR TABLE `%s%s`', Config::get('db.prefix'), $table);
        return Db::execute($sql);
    }

    /**
     * 优化表
     * @param string $table
     * @return mixed
     */
    public function optimize($table)
    {
        $sql = sprintf('OPTIMIZE TABLE `%s%s`', Config::get('db.prefix'), $table);
        return Db::execute($sql);
    }

    /**
     * 获取数据库大小
     * @param string $database
     * @return int
     */
    public function getDataBaseSize($database = '')
    {
        $sql = sprintf('SHOW TABLE STATUS FROM `%s`', ($database ?: Config::get('db.database')));
        $rows = Db::query($sql);
        $size = 0;
        foreach ($rows as $row) {
            $size += $row['Data_length'] + $row['Data_length'] + $row['Index_length'];
        }
        return $size;
    }

    /**
     * 获取表大小
     * @param string $table
     * @return int
     */
    public function getTableSize($table)
    {
        $table = Config::get('db.prefix') . $table;
        $sql = sprintf('SHOW TABLE STATUS FROM `%s`', Config::get('db.database'));
        $rows = Db::query($sql);
        foreach ($rows as $row) {
            if ($row['Name'] == $table) {
                return $row['Data_length'] + $row['Index_length'];
            }
        }
        return 0;
    }

    /**
     * 锁表
     * @param array|string $tables
     * @return mixed
     */
    public function lock($tables)
    {
        $lock = '';
        $prefix = Config::get('db.prefix');
        foreach (explode(',', $tables) as $table) {
            $lock .= '`' . $prefix . trim($table) . '` WRITE,';
        }
        $sql = sprintf('LOCK TABLES %s', substr($lock, 0, -1));
        return Db::execute($sql);
    }

    /**
     * 解锁表
     * @return mixed
     */
    public function unlock()
    {
        return Db::execute('UNLOCK TABLES');
    }

    /**
     * 清空表
     * @param string $table
     * @return mixed
     */
    public function truncate($table)
    {
        $sql = sprintf('TRUNCATE `%s%s`', Config::get('db.prefix'), $table);
        return Db::execute($sql);
    }

    /**
     * 获取所有表信息
     * @param string $database
     * @return array
     */
    public function getAllTableInfo($database = '')
    {
        $database = $database ?: Config::get('db.database');
        $sql = sprintf('SHOW TABLE STATUS FROM `%s`', $database);
        $rows = Db::query($sql);
        $info = [];
        foreach ((array)$rows as $row) {
            $info['table'][$row['Name']]['tablename'] = $row['Name'];
            $info['table'][$row['Name']]['engine'] = $row['Engine'];
            $info['table'][$row['Name']]['rows'] = $row['Rows'];
            $info['table'][$row['Name']]['collation'] = $row['Collation'];
            $charset = $info['table'][$row['Name']]['collation'] = $row['Collation'];
            $charset = explode('_', $charset);
            $info['table'][$row['Name']]['charset'] = $charset[0];
            $info['table'][$row['Name']]['dataFree'] = $row['Data_free']; // 碎片大小
            $info['table'][$row['Name']]['indexSize'] = $row['Index_length']; // 索引大小
            $info['table'][$row['Name']]['dataSize'] = $row['Data_length']; // 数据大小
            $info['table'][$row['Name']]['totalSize'] = $row['Data_free'] + $row['Data_length'] + $row['Index_length'];
        }
        return $info;
    }

    /**
     * 表是否存在
     * @param string $table
     * @return boolean
     */
    public function tableExists($table)
    {
        $tables = Db::query('SHOW TABLES');
        foreach ($tables as $k => $table) {
            $key = 'Tables_in_' . Config::get('db.database');
            if (strtolower($table[$key]) == strtolower(Config::get('db.prefix') . $table)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 字段是否存在
     * @param string $field
     * @param string $table
     * @return boolean
     */
    public function fieldExists($field, $table)
    {
        if ($this->tableExists($table)) {
            $sql = sprintf('DESC `%s%s`', Config::get('db.prefix'), $table);
            $fieldLists = Db::query($sql);
            foreach ((array)$fieldLists as $fieldList) {
                if (strtolower($fieldList['Field']) == strtolower($field)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 执行sql
     * @param string $sql
     * @return boolean
     */
    public function sql($sql)
    {
        $result = preg_split('/;(\r|\n)/is', $sql);
        foreach ((array)$result as $r) {
            Db::execute($r);
        }
        return true;
    }
}
