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

namespace funsent\foundation\response;

use InvalidArgumentException;

/**
 * 响应工厂
 */
class ResponseFactory
{
    /**
     * 原始数据
     * @var mixed
     */
    protected $data;

    /**
     * 原始数据经过解析后的响应数据
     * @var mixed
     */
    protected $content = null;

    /**
     * 响应驱动
     * @var object
     */
    protected $driver;

    /**
     * 响应内容类型
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * 响应编码
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 响应状态码
     * @var integer
     */
    protected $code = 200;

    /**
     * 响应头信息
     * @var array
     */
    protected $headers = [
        'Server'       => 'funsent-ngx v1.0',
        'X-Powered-By' => 'funsent',
    ];

    /**
     * 初始化响应参数
     * @param mixed $data 原始数据
     * @param integer $code
     * @param array $headers
     */
    public function __construct($data = '', $code = 200, $headers = array())
    {
        $this->data($data);
        $this->code($code);
        $this->headers($headers);
        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * 创建响应驱动实例
     * @param string $data 原始数据
     * @param string $driver 驱动，支持：Json、Jsonp、Redirect、Xml
     * @param integer $code
     * @param array $headers
     * @return object
     */
    public function make($data = '', $driver = '', $code = 200, $headers = array())
    {
        if ($data instanceof self) {
            return $data;
        }
        $this->driver = strtolower($driver);
        $class = ucfirst($this->driver);
        if (class_exists($class)) {
            $response = new $class($data, $code, $headers);
        } else {
            $response = new self($data, $code, $headers);
        }
        return $response;
    }

    /**
     * 发送数据到客户端
     * @return mixed
     */
    public function send()
    {
        // 获取输出内容
        $content = $this->content();

        // if (200 == $this->code) {
        //     if ($cache = Request::cache()) {
        //         $this->header['Cache-Control'] = 'max-age=' . $cache[1] . ',must-revalidate';
        //         $this->header['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
        //         $this->header['Expires']       = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $cache[1]) . ' GMT';
        //         Cache::tag($cache[2])->set($cache[0], [$content, $this->header], $cache[1]);
        //     }
        // }

        // 打开输出控制缓冲
        // function_exists('ob_gzhandler') ? ob_start('ob_gzhandler') : ob_start();
        if (Config::get('app.output_compression') && extension_loaded('zlib')) {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', '3');
        }
        ob_start();

        // 发送响应代码和响应头信息
        if (!headers_sent() && !empty($this->headers)) {
            if (version_compare(PHP_VERSION, '5.4.0', '>=')) { // PHP5.4.0+
                http_response_code($this->code);
            }
            foreach ($this->headers as $key => $value) {
                '' === $value ? header($key) : header($key . ':' . $value);
            }
        }

        // 写入输出缓冲区
        echo $content;

        // 输出缓冲区内容并关闭
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_end_flush();
            flush();
        }
    }

    /**
     * 解析原始数据
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        return $data;
    }

    /**
     * 解析响应数据
     * @param mixed $content
     * @return string
     */
    protected function parseContent($content)
    {
        // 存在驱动情况下直接返回，因为驱动会返回解析后的内容
        if (!empty($this->driver)) {
            return $content;
        }
        // 响应驱动为空时自动处理输出内容格式
        if (is_scalar($content) || is_null($content)) {
            // 如果内容类型是：integer、float、string、boolean、null，则以字符串方式返回
            return strval($content);
        } elseif (is_array($content)) {
            // 如果内容类型是数组，则以json格式返回
            return json_encode($content, JSON_UNESCAPED_UNICODE);
        } elseif ($content instanceof View) {
            // 如果内容类型是视图实现类的实例，则以视图解析后的内容返回
            return $content->fetch();
        } else {
            // 其他内容类型，必须检查是否有__toString()方法
            if (!is_callable(array($content, '__toString'))) {
                throw new InvalidArgumentException(sprintf('Invalid variable type %s', gettype($content)));
            }
            return (string)$content;
        }
    }

    /**
     * 设置或获取原始数据
     * @param mixed $data
     * @return this|mixed
     */
    public function data($data = null)
    {
        if (is_null($data)) {
            return $this->data;
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 设置或获取响应数据
     * @param mixed $content
     * @return this|string
     */
    public function content($content = null)
    {
        if (is_null($content)) {
            if (is_null($this->content)) {
                $this->content = $this->parseContent($this->parseData($this->data));
            }
            return $this->content;
        }
        $this->content = $this->parseContent($content);
        return $this;
    }

    /**
     * 设置或获取响应状态码
     * @param integer $code
     * @return this|integer
     */
    public function code($code = null)
    {
        if (is_null($code)) {
            return $this->code;
        }
        $this->code = $code;
        return $this;
    }

    /**
     * 设置或获取响应头信息
     * @param string|array $name
     * @param string $value
     * @return this|mixed
     */
    public function headers($name = null, $value = null)
    {
        if (is_null($name)) {
            return $this->headers;
        } elseif (is_string($name) && is_null($value)) {
            return isset($this->headers[$name]) ? $this->headers[$name] : null;
        }
        $headers = is_array($name) ? $name : array($name => $value);
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * 设置Last-Modified
     * @param string $lastModified
     * @return this
     */
    public function lastModified($lastModified)
    {
        $this->headers('Last-Modified', $lastModified);
        return $this;
    }

    /**
     * 设置Expires
     * @param string $expires
     * @return this
     */
    public function expires($expires)
    {
        $this->headers('Expires', $expires);
        return $this;
    }

    /**
     * 设置ETag
     * @param string $eTag
     * @return this
     */
    public function etag($eTag)
    {
        $this->headers('ETag', $eTag);
        return $this;
    }

    /**
     * 设置缓存控制
     * @param string $cacheControl
     * @return this
     */
    public function cacheControl($cacheControl)
    {
        $this->headers('Cache-Control', $cacheControl);
        return $this;
    }

    /**
     * 设置输出内容类型和编码
     * @param string $contentType
     * @param string $charset
     * @return this
     */
    public function contentType($contentType, $charset = 'utf-8')
    {
        $this->headers('Content-Type', $contentType . '; charset=' . $charset);
        return $this;
    }
}
