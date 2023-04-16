<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\console\command\seed;

use funsent\db\Db;
use funsent\config\Config;
use funsent\schema\Schema;
use funsent\console\build\Output;
use funsent\application\Application;

/**
 * seed命令
 * @package funsent\console\command\seed
 */
class Seed
{
    use Output;

    /**
     * 命名空间
     * @var string
     */
    protected $namespace;

    /**
     * 填充文件根物理路径
     * @var string
     */
    protected $path;

    /**
     * 当前执行的数据库中的编号
     * @var integer
     */
    protected static $batch;

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 设置命名空间和路径
        $this->namespace = sprintf('%s\\common\\database\\seeds', Application::name());
        $this->path = sprintf('%s/common/database/seeds', Application::path());

        // 创建数据表
        if (!Schema::tableExists('seeds')) {
            $sql = sprintf('CREATE TABLE %sseeds(seed VARCHAR(255) NOT NULL,batch INT(11))CHARSET UTF8', Config::get('db.prefix'));
            Db::execute($sql);
        }

        // 设置批次号
        if (empty(self::$batch)) {
            self::$batch = Db::table('seeds')->max('batch') ?: 0;
        }
    }

    /**
     * 执行填充
     * @return boolean
     */
    public function handle()
    {
        return $this->make();
    }

    /**
     * 执行填充
     * @return boolean
     */
    public function make()
    {
        $files = glob($this->path . '/*.php');
        sort($files);
        $db = Db::table('seeds');
        foreach ($files as $file) {
            if (!$db->where('seed', basename($file))->first()) {
                require $file;
                preg_match('@\d{12}_(.+)\.php@', $file, $matches);
                $class = $this->namespace . '\\' . $matches[1];
                (new $class)->up();
                $db->insert([
                    'seed' => basename($file),
                    'batch' => self::$batch + 1,
                ]);
            }
        }
        return true;
    }

    /**
     * 回滚最近一次填充
     * @return boolean
     */
    public function rollback()
    {
        $db = Db::table('seeds');
        $batch = $db->max('batch');
        $files = $db->where('batch', $batch)->lists('seed');
        foreach ($files as $baseFile) {
            $file = $this->path . '/' . $baseFile;
            if (is_file($file)) {
                require $file;
                $class = $this->namespace . '\\' . substr($baseFile, 13, -4);
                (new $class)->down();
            }
            $db->where('seed', $baseFile)->delete();
        }
        return true;
    }

    /**
     * 回滚所有的填充
     * @return boolean
     */
    public function reset()
    {
        $db = Db::table('seeds');
        $files = $db->lists('seed');
        foreach ((array)$files as $baseFile) {
            $file = $this->path . '/' . $baseFile;
            if (is_file($file)) {
                require $file;
                $class = $this->namespace . '\\' . substr($baseFile, 13, -4);
                (new $class)->down();
            }
            $db->where('seed', $baseFile)->delete();
        }
        return true;
    }
}
