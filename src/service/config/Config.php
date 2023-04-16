<?php

namespace funsent\service\config;

use funsent\exception\MethodNotSupportedException;

class Config
{
    protected static $instances = [];

    public function __construct($config = [])
    {
        $this->connect($config);
    }

    protected function connect($config)
    {
        $key = md5(serialize($config));
        $instance = self::$instances[$key] ?? null;
        if (is_null($instance)) {
            $type = $config['type'];
            $class = false !== strpos($type, '\\') ? $type : $this->namespace . Str::studly($type);
            if (!class_exists($class)) {
                throw new Exception('Driver not found');
            }
            self::$instances[$key] = $instance = new $class($config);
        }

        return $instance;
    }

    public function drive(string $type = '', array $config = [])
    {
        if (!empty($type)) {
            $config['type'] = $type;
        }
        return $this->connect($config);
    }

    /**
     * 动态调用
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        if (!method_exists($this->instance, $method)) {
            throw new MethodNotSupportedException(sprintf('method %s not support', $method));
        }
        return call_user_func_array([$this->instance, $method], $args);
    }
}
