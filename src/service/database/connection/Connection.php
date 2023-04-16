<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\connection;

use PDO;
use Closure;
use funsent\config\Config;
use funsent\error\exception\Exception;

/**
 * Trait Connection
 * @package funsent\db\connection
 */
trait Connection
{
    /**
     * 数据库连接配置
     * @var array
     */
    protected $config;

    /**
     * 本次查询影响的条数
     * @var integer
     */
    protected $affectedRow;

    /**
     * 查询语句日志
     * @var array
     */
    protected static $queryLogs = [];

    /**
     * 获取连接
     * @param boolean $type true写 false读
     * @return mixed
     */
    public function link($type = true)
    {
        static $links = [];
        $engine = ($type ? 'write' : 'read');
        $config = Config::get('db.' . $engine);
        $this->config = $config[array_rand($config)];
        $cacheName = serialize($this->config);
        if (!isset($links[$cacheName])) {
            $links[$cacheName] = new PDO(
                $this->getDns(),
                $this->config['user'],
                $this->config['password'],
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"]
            );
            $links[$cacheName]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $links[$cacheName];
    }

    /**
     * 没有结果集的查询
     * @param string $sql
     * @param array $params
     * @return boolean
     * @throws \funsent\error\exception\Exception
     */
    public function execute($sql, array $params = [])
    {
        $sth = $this->link(true)->prepare($sql);
        $params = $this->setParamsSort($params);
        foreach ((array)$params as $key => $value) {
            $sth->bindParam($key, $params[$key], is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            $sth->execute();
            $this->affectedRow = $sth->rowCount();
            self::$queryLogs[] = $sql.var_export($params, true);
            return true;
        } catch (Exception $e) {
            $error = $sth->errorInfo();
			throw new Exception(sprintf('%s; BindParams: %s', $sql, var_export($params, true) . implode(';', $error)));
        }
    }

    /**
     * 重置参数顺序
     * 当绑定的参数以0开始编号时，设置为以1开始编号，这样才可以使用预准备
     * @param array $params
     * @return array
     */
    protected function setParamsSort(array $params)
    {
        if (is_numeric(key($params)) && key($params) == 0) {
            $tmp = [];
            foreach ($params as $key => $value) {
                $tmp[$key + 1] = $value;
            }
            $params = $tmp;
        }
        return $params;
    }

    /**
     * 有返回结果的查询
     * @param string $sql
     * @param array $params
     * @return boolean
     * @throws \funsent\error\exception\Exception
     */
    public function query($sql, array $params = [])
    {
        $sth = $this->link(false)->prepare($sql);
        $sth->setFetchMode(PDO::FETCH_ASSOC);
        $params = $this->setParamsSort($params);
        foreach ((array)$params as $key => $value) {
            $sth->bindParam($key, $params[$key], is_numeric($params[$key]) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            $sth->execute();
            $this->affectedRow = $sth->rowCount();
            self::$queryLogs[] = $sql.var_export($params, true);
            return $sth->fetchAll() ?: [];
        } catch (Exception $e) {
            $error = $sth->errorInfo();
			throw new Exception(sprintf('%s; BindParams: %s', $sql, var_export($params, true) . implode(';', $error)));
        }
    }

    /**
     * 获取受影响条数
     * @return integer
     */
    public function getAffectedRow()
    {
        return $this->affectedRow;
    }

    /**
     * 执行事务，注意事务不能夸数据库
     * @param \Closure $closure
     * @return $this
     * @throws \funsent\error\exception\Exception
     */
    public function transaction(Closure $closure)
    {
        try {
            $this->beginTransaction();
            call_user_func($closure);
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
        }
        return $this;
    }

    /**
     * 开启事务，注意事务不能夸数据库
     * @return $this
     */
    public function beginTransaction()
    {
        $this->link()->beginTransaction();
        return $this;
    }

    /**
     * 事务回滚
     * @return $this
     */
    public function rollback()
    {
        $this->link()->rollback();
        return $this;
    }

    /**
     * 事务提交
     * @return $this
     */
    public function commit()
    {
        $this->link()->commit();
        return $this;
    }

    /**
     * 获取自增主键
     * @return mixed
     */
    public function getInsertId()
    {
        return intval($this->link()->lastInsertId());
    }

    /**
     * 获得查询SQL语句
     * @return array
     */
    public function getQueryLog()
    {
        return self::$queryLogs;
    }
}
