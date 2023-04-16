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

namespace funsent\service\session\driver;

use funsent\service\session\contract\SessionInterface;
use funsent\service\session\SessionFactory;

/**
 * 基于数据库的会话实现
 */
class Db extends SessionFactory  implements SessionInterface
{
    /**
     * 数据库连接实例
     * @var object
     */
    protected $instance;

    /**
     * 打开会话
     * @return void
     */
    public function open()
    {
        $this->instance = Db::table($this->config['table']);
    }

    /**
     * 读取会话
     * @return array
     */
    public function read()
    {
        $data = $this->instance->where('id', $this->id())->pluck('data');
        return $data ? unserialize($data) : array();
    }

    /**
     * 写入会话
     * @return mixed
     */
    public function write()
    {
        $data = [
            'id'         => $this->id(),
            'data'       => serialize($this->data),
            'created_at' => time(),
        ];
        $this->instance->where('session_id', $this->id())->replace($data);
    }

    /**
     * 回收会话
     * @return mixed
     */
    public function gc()
    {
        return $this->instance->where('created_at', '<', time() - $this->config['lifetime'])->where('id', '<>', $this->id())->delete();
    }
}
