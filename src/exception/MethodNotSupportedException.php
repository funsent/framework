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

use BadMethodCallException;

/**
 * 方法不支持异常
 * 
 * @package funsent
 */
class MethodNotSupportedException extends BadMethodCallException
{

}