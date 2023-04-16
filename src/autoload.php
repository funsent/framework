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

namespace funsent;

// 实例化类加载器
require __DIR__ . '/src/loader/ClassLoader.php';
$classLoader = new \funsent\loader\ClassLoader();

// 注册框架命名空间
$classLoader->addPsr4('funsent\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');

// 注册类自动加载
$classLoader->register();

// 返回类加载器
return $classLoader;
