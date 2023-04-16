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

namespace funsent\service\session\contract;

/**
 * 会话接口
 */
interface SessionInterface
{
    /**
     * 打开会话
     * @return boolean
     */
    public function open();

    /**
     * 读取会话
     * @return mixed
     */
    public function read();

    /**
     * 写入会话
     * @return mixed
     */
    public function write();

    /**
     * 回收会话
     * @return boolean
     */
    public function gc();
}
