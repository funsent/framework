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

namespace funsent\exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

class ClassNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    protected $class;

    public function __construct(string $message, string $class = '', Throwable $previous = null)
    {
        $this->message = $message;
        $this->class   = $class;

        parent::__construct($message, 0, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
