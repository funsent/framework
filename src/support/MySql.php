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

use think\Db;

/**
 * MySQL数据库导出模型
 * 
 * @package funsent
 */
class MysqlHelper
{
    /**
     * 文件指针
     * @var resource
     */
    protected $fp;

    /**
     * 配置
     * @var array
     */
    protected $config = [
        'path'      => './database/',   // 备份路径
        'part_size' => '20971520',      // 分卷大小
        'compress'  => true,            // 是否压缩
        'level'     => '9',             // 压缩级别
    ];

    /**
     * 文件信息
     * @var array
     */
    protected $file = [
        'part' => 1, // 分卷号
        'name' => '', // 文件名
    ];

    /**
     * 当前打开文件大小
     * @var integer
     */
    protected $size = 0;

    /**
     * 构造方法
     * @param array $file 备份或还原的文件信息
     * @param array $config 备份配置信息
     * @param string $type 执行类型 backup备份数据,restore还原数据
     */
    public function __construct($file, $config, $type = 'backup')
    {
        $this->file = array_merge($this->file, $file);
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 打开分卷，用于写入数据
     * @param integer $size 写入数据的大小
     * @return void
     */
    protected function open($size)
    {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part_size']) {
                $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                cache('backup_file', $this->file);
                $this->create();
            }
        } else {
            $backupPath = $this->config['path'];
            $filename = "{$backupPath}{$this->file['name']}-{$this->file['part']}.sql";
            if ($this->config['compress']) {
                $filename = "{$filename}.gz";
                $this->fp = gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * 写入初始数据
     * @return boolean
     */
    public function create()
    {
        $year = date('Y');
        $sql  = "-- -----------------------------\n";
        $sql .= "-- MySQL Data Transfer \n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . config('database.hostname') . "\n";
        $sql .= "-- Port     : " . config('database.hostport') . "\n";
        $sql .= "-- Database : " . config('database.database') . "\n";
        $sql .= "-- \n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n";
        $sql .= "-- Version: " . config('app.product.symbol') . ' v' . config('app.product.version') . ' (build:' . config('app.product.build') . ")\n";
        $sql .= "-- Author: yanggf<2018708@qq.com> 15058001088\n";
        $sql .= "-- Copyright©{$year} funsent.com Inc. All rights reserved.\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }

    /**
     * 写入SQL
     * @param string $sql
     * @return boolean
     */
    private function write($sql)
    {
        $size = strlen($sql);

        // 由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        // 一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;
        $this->open($size);
        return $this->config['compress'] ? gzwrite($this->fp, $sql) : fwrite($this->fp, $sql);
    }

    /**
     * 备份表
     * @param string $table 表名
     * @param integer $start 起始行数
     * @return boolean
     */
    public function backup($table, $start)
    {
        // 备份表结构
        if (0 == $start) {
            $result = Db::query("SHOW CREATE TABLE `{$table}`");
            $result = array_map('array_change_key_case', $result);
            $sql  = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[0]['create table']) . ";\n\n";
            if (false === $this->write($sql)) {
                return false;
            }
        }

        // 数据总数
        $result = Db::query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count = $result[0]['count'];

        // 备份表数据
        if ($count) {
            // 写入注释
            if (0 == $start) {
                $sql  = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }

            // 备份记录
            $result = Db::query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $row) {
                $row = array_map('addslashes', $row);
                $values = str_replace(["\r", "\n"], ['\r', '\n'], implode("', '", $row));
                $sql = "INSERT INTO `{$table}` VALUES ('" . $values . "');\n";
                if (false === $this->write($sql)) {
                    return false;
                }
            }

            // 还有更多数据
            if ($count > $start + 1000) {
                return [$start + 1000, $count];
            }
        }

        // 备份下一张表
        return 0;
    }

    /**
     * 还原数据
     * @param integer $start 指针位置
     * @return array
     */
    public function restore($start)
    {
        if ($this->config['compress']) {
            $handle = gzopen($this->file['name'], 'r');
            $size = 0;
        } else {
            $handle = fopen($this->file['name'], 'r');
            $size = filesize($this->file['name']);
        }
        // 还原数据前定位指针位置在具体行上
        $sql = '';
        if ($start) {
            $this->config['compress'] ? gzseek($handle, $start) : fseek($handle, $start);
        }
        // 还原SQL到数据库
        for ($i = 0; $i < 1000; $i++) {
            $sql .= $this->config['compress'] ? gzgets($handle) : fgets($handle);
            if (preg_match('/.*;$/', trim($sql))) {
                if (false !== Db::execute($sql)) { // 单条SQL写入
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($handle) : feof($handle)) {
                // 下一张表
                return 0;
            }
        }
        $lastTotal = cache('restore_total');
        $newTotal = $lastTotal + $start;
        cache('restore_total', $newTotal);
        cache('restore_rate', round(100 * ($lastTotal / $newTotal), 3)); // 还原进度
        // 更多数据
        return [$start, $size];
    }

    /**
     * 析构方法
     * @return void
     */
    public function __destruct()
    {
        if ($this->fp) {
            $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
        }
    }
}
