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
 * 基于Redis的会话实现
 */
class Redis extends SessionFactory implements SessionInterface
{
    /**
     * Redis连接实例
     * @var object
     */
	protected $instance;

    /**
     * 打开会话
     * @return void
     */
    public function open()
	{
        $instance = new Redis();
        if ($instance->connect($this->config['host'], $this->config['port'])) {
            $instance->auth($this->config['password']);
            $instance->select($this->config['database']);
        }
        $this->instance = $instance;
	}

    /**
     * 读取会话
     * @return array
     */
	function read()
	{
		$data = $this->instance->get($this->id());
		return $data ? json_decode($data, true) : array();
	}

    /**
     * 写入会话
     * @return mixed
     */
	function write()
	{
		return $this->instance->set($this->id(), json_encode($this->data ,JSON_UNESCAPED_UNICODE));
	}

    /**
     * 垃圾回收会话
     * @return mixed
     */
	function gc()
	{
	}
}
