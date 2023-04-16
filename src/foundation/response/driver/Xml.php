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

/**
 * Xml响应类
 * @package funsent
 * @author yanggf
 */
class Xml extends ResponseFactory
{
    /**
     * 内容类型
     * @var string
     */
    protected $contentType = 'text/xml';

    /**
     * 解析原始数据
     * @param array $data
     * @return string
     */
    protected function parseData($data)
    {
        return Xml::arrayToXml('funsent', $data);
    }
}
