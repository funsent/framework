<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\connection;

use funsent\db\contract\DbInterface;

/**
 * mysql连接器实现
 * @package funsent\db\connection
 */
class Mysql implements DbInterface
{
    use Connection;

    /**
     * 获取dns连接字符串
     * @return string
     */
    public function getDns()
    {
        return $dns = 'mysql:dbname=' . $this->config['database'] . ';host=' . $this->config['host'] . ';port=' . $this->config['port'];
    }
}
