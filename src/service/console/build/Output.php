<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\console\build;

/**
 * Trait Output
 * @package funsent\console\build
 */
trait Output
{
    /**
     * 输出错误信息
     * @param string $msg
     * @return void
     */
    public function error($msg)
    {
        die(sprintf("\033[;41m%s\033[0m", $msg));
    }

    /**
     * 成功
     * @param string $msg
     * @return void
     */
    public function success($msg)
    {
        die(sprintf("\033[;36m%s\033[0m", $msg));
    }
}
