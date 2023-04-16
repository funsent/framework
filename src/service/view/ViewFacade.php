<?php

/**
 * funsent - A PHP Framework For Web Application
 *
 * @link      http://www.funsent.com/
 * @copyright 2020 funsent.com, Inc.
 * @author    yanggf <2018708@qq.com>
 * @package   funsent
 * @version   1.1.2
 */

namespace funsent\view;

use funsent\contract\Facade;

/**
 * 视图服务门面
 * 
 * @package funsent\kernel\view
 */
class ViewFacade extends Facade
{
    /**
     * 获取门面
     * 
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'View';
    }
}
