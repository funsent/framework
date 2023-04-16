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
 * 基于Memcache的会话实现
 */
class Memcache extends SessionFactory implements SessionInterface
{
    /**
     * Memcache连接实例
     * @var object
     */
    protected $instance;

    /**
     * 打开
     * @return void
     */
    public function open()
    {
        $instance = new Memcache();

        // 支持集群
        $hosts = explode(',', $this->config['host']);
        $ports = explode(',', $this->config['port']);

        // 建立连接
        foreach ($hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $this->config['timeout'] ?
                $instance->addServer($host, $port, $this->config['persistent'], 1, $this->config['timeout']) :
                $instance->addServer($host, $port, $this->config['persistent'], 1);
        }

        // 保存实例
        $this->instance = $instance;
    }

    /**
     * 读取
     * @return array
     */
    public function read()
    {
        $data = $this->instance->get($this->id());
        return $data ? json_decode($data, true) : array();
    }

    /**
     * 写入
     * @return mixed
     */
    public function write()
    {
        return $this->instance->set($this->id(), json_encode($this->data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 垃圾回收
     * @return mixed
     */
    public function gc()
    {
    }
}
