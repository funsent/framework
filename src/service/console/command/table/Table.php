<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */


namespace funsent\console\command\table;

use funsent\config\Config;
use funsent\schema\Schema;
use funsent\console\build\Output;

/**
 * table命令
 * @package funsent\console\command\table
 */
class Table
{
    use Output;

    /**
     * 创建缓存表
     * @return void
     */
    public function cache()
    {
        $prefix = Config::get('db.prefix');
        $table = Config::get('cache.mysql.table');
        if (Schema::tableExists($table)) {
            $this->error('Duplicate table' . $table);
        }
        $sql = <<<SQL_EOL
CREATE TABLE `{$prefix}{$table}` (
    `id` char(64) NOT NULL,
    `data` mediumtext,
    `check` char(32) NOT NULL,
    `lifetime` int(8) NOT NULL,
    `create_at` int(11) NOT NULL,
    UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL_EOL;
        Schema::sql($sql);
    }

    /**
     * 创建会话表
     * @return void
     */
    public function session()
    {
        $prefix = Config::get('db.prefix');
        $table = Config::get('session.mysql.table');
        if (Schema::tableExists($table)) {
            $this->error('Duplicate table' . $table);
        }
        $sql = <<<SQL_EOL
CREATE TABLE `{$prefix}{$table}` (
    `id` char(32) NOT NULL,
    `data` mediumtext,
    `create_at` int(11) NOT NULL,
    UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL_EOL;
        Schema::sql($sql);
    }

    /**
     * 创建日志表
     * @return void
     */
    public function log()
    {
        $prefix = Config::get('db.prefix');
        $table = Config::get('logger.mysql.table');
        if (Schema::tableExists($table)) {
            $this->error('Duplicate table' . $prefix . $table);
        }
        $sql = <<<SQL_EOL
CREATE TABLE `{$prefix}{$table}` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `msg` text,
    `level` char(20) DEFAULT NOT NULL,
    `create_at` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL_EOL;
        Schema::sql($sql);
    }
}
