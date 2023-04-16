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
 * 基于Memcached的会话实现
 */
class Memcached extends SessionFactory implements SessionInterface
{
    /**
     * Memcached连接实例
     * @var object
     */
    protected $instance;

    /**
     * 打开会话
     * @return void
     */
    public function open()
    {
        $instance = new Memcached();

        // 设置连接超时时间，单位毫秒
        if ($this->config['timeout'] > 0) {
            $instance->setOption(Memcached::OPT_CONNECT_TIMEOUT, $this->config['timeout']);
        }

        // 支持集群
        $hosts = explode(',', $this->config['host']);
        $ports = explode(',', $this->config['port']);

        // 建立连接
        $servers = array();
        foreach ($hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $servers[] = array($host, $port, 1);
        }
        $instance->addServers($servers);
        if (! empty($this->config['username'])) {
            $instance->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $instance->setSaslAuthData($this->config['username'], $this->config['password']);
        }

        // 保存实例
        $this->instance = $instance;
    }

    /**
     * 读取会话
     * @return array
     */
    public function read()
    {
        $data = $this->instance->get($this->id());
        return $data ? json_decode($data, true) : array();
    }

    /**
     * 写入会话
     * @return mixed
     */
    public function write()
    {
        return $this->instance->set($this->id(), json_encode($this->data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 回收会话
     * @return mixed
     */
    public function gc()
    {
    }
}
