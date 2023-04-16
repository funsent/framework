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

namespace funsent\service\session;

/**
 * 会话抽象基类
 */
abstract class SessionFactory
{
    /**
     * 参数
     * @var array
     */
    protected $config = array(
        'prefix'   => 'fs',
        'name'     => '',
        'domain'   => '',
        'lifetime' => 0,
        'flash_id' => '__SESSION_FLASH__',
    );

    /**
     * 数据
     * @var array
     */
    protected $data = array();

    /**
     * 开始时间，用于一次性数据操作
     * @var float
     */
    protected static $flashStartTime;

    /**
     * 构造方法
     * @param array $config
     */
    final public function __construct($config)
    {
        // 保存配置
        $this->config = array_merge($this->config, $config);

        // 打开
        $this->open();

        // 读取
        $content = $this->read();
        $this->data = is_array($content) ? $content : array();

        // 设置开始时间
        if (empty(self::$flashStartTime)) {
            self::$flashStartTime = microtime(true);
        }
    }

    /**
     * 获取会话ID，这里是实现的关键
     * @return string
     */
    final protected function id()
    {
        $id = Cookie::get($this->config['name']);
        $id = $id ? $id : md5(microtime(true) . mt_rand(1, 6));
        Cookie::set($this->config['name'], $id, $this->config['lifetime'], '/', $this->config['domain']);
        return $id;
    }

    /**
     * 设置会话
     * @param string $name 支持通过.实现多级设置
     * @param mixed $value
     * @return object
     */
    final public function set($name, $value)
    {
        $data = &$this->data;
        $names = explode('.', $name);
        foreach ($names as $name) {
            if (!isset($data[$name])) {
                $data[$name] = array();
            }
            $data = &$data[$name];
        }
        $data = $value;
        return $this;
    }

    /**
     * 放入会话
     * @param string $name 支持通过.实现多级设置
     * @param mixed $value
     * @return object
     */
    final public function put($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * 批量设置
     * @param array $data
     * @return object
     */
    final public function batch($data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * 检测会话
     * @param string $name
     * @return boolean
     */
    final public function has($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * 获取会话
     * @param string $name
     * @param string $defalut 默认值
     * @return mixed
     */
    final public function get($name, $defalut = null)
    {
        $data = $this->data;
        $names = explode('.', $name);
        foreach ($names as $name) {
            if (isset($data[$name])) {
                $data = $data[$name];
            } else {
                return $defalut;
            }
        }
        return $data;
    }

    /**
     * 弹出会话
     * @param string $name
     * @param string $defalut 默认值
     * @return mixed
     */
    final public function pull($name, $defalut = null)
    {
        $value = $this->get($name, $defalut);
        $this->delete($name);
        return $value;
    }

    /**
     * 获取所有会话
     * @return mixed
     */
    final public function all()
    {
        return $this->data;
    }

    /**
     * 删除会话
     * @param string $name
     * @return $this
     */
    final public function delete($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
        return $this;
    }

    /**
     * 删除会话
     * @param string $name
     * @return $this
     */
    final public function forget($name)
    {
        return $this->delete($name);
    }

    /**
     * 清空会话
     * @return $this
     */
    final public function clear()
    {
        $this->data = array();
        return $this;
    }

    /**
     * 一次性会话数据操作
     * @param null|array|string $name
     * @param null|array|string $value
     * @return $this|mixed
     */
    final public function flash($name = '', $value = '')
    {
        $flashId = $this->config['flash_id'];
        if (is_null($name)) {
            // 删除所有一次性会话数据
            return $this->delete($flashId);
        } elseif (empty($name)) {
            // 获取所有一次性会话数据
            $data = $this->get($flashId);
            return $data ? $data : array();
        } elseif (is_array($name)) {
            // 批量设置一次性会话数据
            foreach ($name as $key => $value) {
                $this->set($flashId . '.' . $key, array(self::$flashStartTime, $value));
            }
        } elseif (is_string($name)) {
            if (is_null($value)) {
                // 删除单个一次性会话数据
                if (isset($this->data[$flashId][$name])) {
                    unset($this->data[$flashId][$name]);
                }
            } elseif (empty($value)) {
                // 获取单个一次性会话数据
                if ($data = $this->get($flashId . '.' . $name)) {
                    return $data[1];
                }
                return null;
            } else {
                // 设置单个一次性会话数据
                return $this->set($flashId . '.' . $name, array(self::$flashStartTime, $value));
            }
        }
        return $this;
    }

    /**
     * 清理无效一次性会话数据
     * @return $this
     */
    final public function cleanFlash()
    {
        foreach ($this->flash() as $name => $value) {
            if ($value[0] != self::$flashStartTime) {
                $this->flash($name, null);
            }
        }
        return $this;
    }

    /**
     * 关闭会话，写入数据，并执行垃圾清理
     * @return boolean
     */
    final public function close()
    {
        $this->write();
        if (mt_rand(1, 50) == 1) {
            $this->gc();
        }
    }

    /**
     * 析构函数，清理无效一次性数据、写入数据、执行垃圾清理
     * @return void
     */
    final public function __destruct()
    {
        $this->cleanFlash();
        $this->close();
    }
}
