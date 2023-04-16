<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\build;

/**
 * Mysql构造器实现
 * @package funsent\db\build
 */
class Mysql extends Build
{
    /**
     * 构造select语句
     * @return string
     */
    public function select()
    {
        $sql = 'SELECT %field% FROM %table% %join% %where% %groupBy% %having% %orderBy% %limit% %lock%';
        return str_replace([
            '%field%',
            '%table%',
            '%join%',
            '%where%',
            '%groupBy%',
            '%having%',
            '%orderBy%',
            '%limit%',
            '%lock%'
        ], [
            $this->parseField(),
            $this->parseTable(),
            $this->parseJoin(),
            $this->parseWhere(),
            $this->parseGroupBy(),
            $this->parseHaving(),
            $this->parseOrderBy(),
            $this->parseLimit(),
            $this->parseLock()
        ], $sql);
    }

    /**
     * 构造insert语句
     * @return string
     */
    public function insert()
    {
        $sql = 'INSERT INTO %table%(%field%) VALUES(%values%)';
        return str_replace([
            '%table%',
            '%field%',
            '%values%'
        ], [
            $this->parseTable(),
            $this->parseField(),
            $this->parseValues(),
        ], $sql);
    }

    /**
     * 构造replace语句
     * @return string
     */
    public function replace()
    {
        $sql = 'REPLACE INTO %table%(%field%) VALUES(%values%)';
        return str_replace([
            '%table%',
            '%field%',
            '%values%'
        ], [
            $this->parseTable(),
            $this->parseField(),
            $this->parseValues(),
        ], $sql);
    }

    /**
     * 构造update语句
     * @return string
     */
    public function update()
    {
        $sql = 'UPDATE %table% %set% %where%';
        return str_replace([
            '%table%',
            '%set%',
            '%where%'
        ], [
            $this->parseTable(),
            $this->parseSet(),
            $this->parseWhere()
        ], $sql);
    }

    /**
     * 构造delete语句
     * @return string
     */
    public function delete()
    {
        $sql = 'DELETE FROM %table% %using% %where%';
        return str_replace([
            '%table%',
            '%using%',
            '%where%'
        ], [
            $this->parseTable(),
            $this->parseUsing(),
            $this->parseWhere(),
        ], $sql);
    }
}
