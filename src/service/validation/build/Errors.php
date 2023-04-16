<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\validater\build;

/**
 * 验证错误
 * @package funsent\validater\build
 */
class Errors
{
    /**
     * 错误信息
     * @var array
     */
    protected $errors = [];

    /**
     * 获取错误信息
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 设置错误信息
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }
}
