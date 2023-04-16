<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\build;

use funsent\config\Config;

/**
 * 数据库服务实现抽象基类
 * @package funsent\db\build
 * SELECT %field% FROM %table% JOIN %join% WHERE %where% GROUP BY %group% HAVING %having% ORDER BY %order% LIMIT %limit%
 * INSERT INTO %table% (%field%) VALUES(%values%)
 * REPLACE INTO %table% (%field%) VALUES(%values%)
 * UPDATE %table% SET %set% WHERE %where%
 * DELETE FROM %table% USING  %using% WHERE %where%
 */
abstract class Build
{
    /**
     * 查询器实例
     * @var \funsent\db\query\Query
     */
    protected $query;

    /**
     * 查询参数
     * @var array
     */
    protected $params = [];

    /**
     * 构造select语句
     * @return string
     */
    abstract public function select();

    /**
     * 构造insert语句
     * @return string
     */
    abstract public function insert();

    /**
     * 构造replace语句
     * @return string
     */
    abstract public function replace();

    /**
     * 构造update语句
     * @return string
     */
    abstract public function update();

    /**
     * 构造delete语句
     * @return string
     */
    abstract public function delete();

    /**
     * 构造方法
     * @param object $query
     */
    public function __construct($query)
    {
        $this->query  = $query;
    }

    /**
     * 绑定表达式
     * @param string $name
     * @param string $expression
     * @return void
     */
    public function bindExpression($name, $expression)
    {
        $this->params[$name]['expression'][] = $expression;
    }

    /**
     * 获取绑定表达式
     * @param string $name
     * @return array
     */
    public function getBindExpression($name)
    {
        return isset($this->params[$name]['expression']) ? $this->params[$name]['expression'] : [];
    }

    /**
     * 绑定参数
     * @param string $name
     * @param string $param
     * @return void
     */
    public function bindParams($name, $param)
    {
        $this->params[$name]['parames'][] = $param;
    }

    /**
     * 获取绑定参数
     * @param string $name
     * @return array
     */
    public function getBindParams($name)
    {
        return isset($this->params[$name]['parames']) ? $this->params[$name]['parames'] : [];
    }

    /**
     * reset参数
     * @return void
     */
    public function reset()
    {
        $this->params = [];
    }

    /**
     * 获取select参数
     * @return array
     */
    public function getSelectParams()
    {
        $params = [];
        $id = 0;
        foreach (['field', 'join', 'where', 'group', 'having', 'order', 'limit'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }
        return $params;
    }

    /**
     * 获取insert参数
     * @return array
     */
    public function getInsertParams()
    {
        $params = [];
        $id = 0;
        foreach (['field', 'values'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }
        return $params;
    }

    /**
     * 获取update参数
     * @return array
     */
    public function getUpdateParams()
    {
        $params = [];
        $id = 0;
        foreach (['set', 'values', 'where'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }
        return $params;
    }

    /**
     * 获取delete参数
     * @return array
     */
    public function getDeleteParams()
    {
        $params = [];
        $id = 0;
        foreach (['where'] as $k) {
            foreach ($this->getBindParams($k) as $m) {
                $params[++$id] = $m;
            }
        }
        return $params;
    }

    /**
     * 解析table
     * @return string
     */
    protected function parseTable()
    {
        return $this->query->getTable();
    }

    /**
     * 解析field
     * @return string
     */
    protected function parseField()
    {
        $expression = $this->getBindExpression('field');
        return $expression ? implode(',', $expression) : '*';
    }

    /**
     * 解析value
     * @return string
     */
    protected function parseValues()
    {
        $values = [];
        foreach ($this->params['values']['expression'] as $key => $value) {
            $values[] = '?';
        }
        return implode(',', $values);
    }

    /**
     * 解析joins
     * @return string
     */
    public function parseJoin()
    {
        $expression = $this->getBindExpression('join');
        $as = preg_replace('/^' . Config::get('db.prefix') . '/', '', $this->parseTable());
        return $expression ? ($as . implode(' ', $expression)) : '';
    }

    /**
     * 解析where
     * @return string
     */
    public function parseWhere()
    {
        if ($expression = $this->getBindExpression('where')) {
            return 'WHERE ' . implode(' ', $expression);
        }
    }

    /**
     * 解析group by
     * @return string
     */
    protected function parseGroupBy()
    {
        if ($expression = $this->getBindExpression('groupBy')) {
            return 'GROUP BY ' . implode(',', $expression);
        }
    }

    /**
     * 解析having
     * @return string
     */
    protected function parseHaving()
    {
        if ($expression = $this->getBindExpression('having')) {
            return 'HAVING ' . current($expression);
        }
    }

    /**
     * 解析orderby
     * @return string
     */
    protected function parseOrderBy()
    {
        if ($expression = $this->getBindExpression('orderBy')) {
            return 'ORDER BY ' . implode(',', $expression);
        }
    }

    /**
     * 解析limit
     * @return string
     */
    protected function parseLimit()
    {
        if ($expression = $this->getBindExpression('limit')) {
            return 'LIMIT ' . current($expression);
        }
    }

    /**
     * 解析lock
     * @return string
     */
    protected function parseLock()
    {
        if ($expression = $this->getBindExpression('lock')) {
            return current($expression);
        }
    }

    /**
     * 解析set
     * @return string
     */
    protected function parseSet()
    {
        if ($expression = $this->getBindExpression('set')) {
            $set = '';
            foreach ($expression as $key => $value) {
                $set .= "`{$value}`=?,";
            }
            return $set ? 'SET ' . substr( $set, 0, - 1 ) : '';
        }
    }

    /**
     * 解析using
     * @return string
     */
    protected function parseUsing()
    {
        if ($expression = $this->getBindExpression('using')) {
            return 'USING ' . implode(',', $expression);
        }
    }
}
