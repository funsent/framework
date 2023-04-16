<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\contract;

/**
 * Interface DbInterface
 * @package funsent\db\contract
 */
interface DbInterface
{
    /**
     * 获取dns连接字符串
     * @return string
     */
    public function getDns();
}
