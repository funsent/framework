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

namespace funsent\foundation\response\driver;

use funsent\foundation\response\ResponseFactory;
use InvalidArgumentException;
use Exception;

/**
 * Json响应类
 * @package funsent
 * @author yanggf
 */
class Json extends ResponseFactory
{
    /**
     * 内容类型
     * @var string
     */
    protected $contentType = 'application/json';

    /**
     * 解析原始数据
     * @param array|string $data
     * @return string
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function parseData($data)
    {
        try {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (false === $data) {
                throw new InvalidArgumentException(json_last_error_msg());
            }
            return $data;
        } catch (Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }
}
