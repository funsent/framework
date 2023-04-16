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

namespace funsent\service\session\driver;

use funsent\service\session\contract\SessionInterface;
use funsent\service\session\SessionFactory;

/**
 * 基于文件系统的会话实现
 */
class Filesystem extends SessionFactory implements SessionInterface
{
    /**
     * 打开会话
     * @return boolean
     */
    public function open()
    {
        $path = App::instance()->config('runtime_path') . $this->config['dir'];
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true)) {
                return false;
            }
        }
        $this->config['path'] = $path;
        $this->config['file'] = $path . DIRECTORY_SEPARATOR . $this->id() . $this->config['extension'];
        return true;
    }

    /**
     * 读取会话
     * @return mixed
     */
    public function read()
    {
        if (is_file($this->config['file'])) {
            return unserialize(file_get_contents($this->config['file']));
        }
        return null;
    }

    /**
     * 写入会话
     * @return mixed
     */
    public function write()
    {
        return file_put_contents($this->config['file'], serialize($this->data), LOCK_EX);
    }

    /**
     * 回收会话
     * @return boolean
     */
    public function gc()
    {
        foreach (glob($this->config['path'] . '/*' . $this->config['extension']) as $file) {
            if (is_file($file) && (filemtime($file) + $this->config['lifetime']) < time()) {
                unlink($file);
            }
        }
        return true;
    }
}
