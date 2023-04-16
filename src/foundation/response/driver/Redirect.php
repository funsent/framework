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

use Session;
use Request;

/**
 * 重定向响应类
 * @package funsent
 * @author yanggf
 */
class Redirect extends ResponseFactory
{
    /**
     * url参数
     * @var array
     */
    protected $args = array();

    /**
     * 构造方法
     * @param mixed $data
     * @param int $code
     * @param array $header
     * @param array $option
     */
    public function __construct($data = '', $code = 302, $header = array())
    {
        parent::__construct($data, $code, $header);
    }

    /**
     * 解析原始数据
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        $this->headers('Location', $this->url());
        $this->cacheControl('no-cache,must-revalidate');
        return $data;
    }

    /**
     * 重定向传值
     * @param string|array $name
     * @param mixed $value
     * @return this
     */
    public function with($name, $value = '')
    {
        $vars = is_array($name) ? $name : array($name => $value);
        foreach ($vars as $key => $value) {
            Session::flash($key, $value);
        }
        return $this;
    }

    /**
     * 获取跳转地址
     * @return string
     */
    public function url()
    {
        if (strpos($this->data, '://')) {
            if ($this->args) {
                $args = http_build_query($this->args);
                if (strpos($this->data, '?')) {
                    $this->data = preg_replace('#(\?)#', '$1' . $args . '&', $this->data);
                } else {
                    $this->data .= '?' . $args;
                }
            }
            return $this->data;
        } else {
            return Url::build($this->data, $this->args);
        }
    }

    /**
     * 设置参数
     * @param array $args
     * @return this
     */
    public function args($args = array())
    {
        $this->args = $args;
        return $this;
    }

    /**
     * 记住当前URL后跳转
     * @return this
     */
    public function remember()
    {
        Session::set('referer_url', Request::url());
        return $this;
    }

    /**
     * 跳转到上次记住的URL
     * @return this
     */
    public function restore()
    {
        if (Session::has('referer_url')) {
            $this->data = Session::pull('referer_url');
        }
        return $this;
    }
}
