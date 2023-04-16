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

namespace funsent\helper;

use funsent\helper\xml\Array2XML;
use funsent\helper\xml\XML2Array;

/**
 * Xml处理类
 * 
 * @author yanggf
 */
class Xml
{
    /**
     * array转为xml
     * 
     * @param string $rootNodeName
     * @param array $data
     * @return string
     */
    public static function arrayToXml($rootNodeName, $data)
    {
        $xml = Array2XML::createXML($rootNodeName, $data);
        return $xml->saveXML();
    }

    /**
     * xml转为array
     * 
     * @param array|DOMDocument $xml
     * @return array
     */
    public static function xmlToArray($xml)
    {
        return XML2Array::createArray($xml);
    }

    /**
     * 生成简单的xml数据，不能分析复杂的XML数据，比如有属性的XML
     * 
     * @param array $data
     * @param integer $level
     * @return string
     */
    public static function toSimpleXml($data, $level = 0)
    {
        if ($level == 0) {
            $xml = '<xml>';
        }
        foreach ((array) $data as $key => $value) {
            if (is_array($value)) {
                $xml .= '<' . $key . '>' . self::toSimpleXml($value, 1) . '</' . $key . '>';
            } elseif (is_numeric($value)) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
            }
        }
        if ($level == 0) {
            $xml .= '</xml>';
        }
        return $xml;
    }

    /**
     * 生成简单的array数据，不分析XML属性等数据
     * 
     * @param string $xml
     * @return mixed
     */
    public static function toSimpleArray($xml)
    {
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string((string) $xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}
