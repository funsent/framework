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

use funsent\foundation\App;

use funsent\foundation\http\Kernel as HttpKernel;
use funsent\foundation\http\Request as HttpRequest;

use funsent\foundation\console\Kernel as ConsoleKernel;
use funsent\foundation\console\input\Input as ConsoleInput;
use funsent\foundation\console\output\Output as ConsoleOutput;

class Application
{
    /**
     * http应用启动入口
     *
     * @param string $rootPath
     * @return void
     */
    public static function runAsHttp(string $rootPath = ''): void
    {
        $app = new App($rootPath);

        $kernel = $app->make(HttpKernel::class);

        $response = $kernel->handle(
            $request = HttpRequest::capture()
        );

        $response->send();

        $kernel->terminate($request, $response);
    }

    /**
     * console应用启动入口
     *
     * @param string $path
     * @return void
     */
    public static function runAsConsole(string $rootPath = ''): void
    {
        $app = new App($rootPath);

        $kernel = $app->make(ConsoleKernel::class);

        $status = $kernel->handle(
            $input = new ConsoleInput, new ConsoleOutput
        );
        
        $kernel->terminate($input, $status);
        
        exit($status);
    }
}
