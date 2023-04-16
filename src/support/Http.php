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

/**
 * Http工具类
 */
class Http
{
    /**
     * 用cURL发起一个请求
     *
     * @param string $url
     * @param array $args
     * @param string $method
     * @param array $options for cURL
     * @return mixed
     */
    public static function curlRequest($url, $args, $method = 'POST', $options = [])
    {
        switch (strtoupper($method)) {
            case 'POST':
                $config = [
                    CURLOPT_URL            => $url,
                    CURLOPT_POST           => 1,
                    CURLOPT_HEADER         => 0,
                    CURLOPT_FRESH_CONNECT  => 1,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_FORBID_REUSE   => 1,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSLVERSION     => 3,
                    CURLOPT_POSTFIELDS     => http_build_query($args)
                ];
                break;
            case 'GET':
            default:
                $url = $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($args);
                $config = [
                    CURLOPT_URL            => $url,
                    CURLOPT_HEADER         => 0,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_TIMEOUT        => 15
                ];
                break;
        }

        curl_setopt_array($ch = curl_init(), ($options + $config));
        if (false === ($result = curl_exec($ch))) {
            $error = curl_error($ch);
        }
        curl_close($ch);

        return $error ?? $result;
    }

    /**
     * 文件下载
     * 
     * @param string $file
     * @param string|null $newFilename
     * @return mixed
     */
    public static function download($file, $newFilename = null)
    {
        if (!is_file($file)) {
            return false;
        }

        if (!($handle = fopen($file, 'rb'))) {
            return false;
        }

        $filesize = filesize($file);

        $filename = $newFilename ?: basename($file);

        // 文件传输的内容
        header('Content-Description: File Transfer');

        // 输出字节流（因为不知道文件是什么类型）
        header('Content-Type: application/octet-stream');

        // 设置附件方式下载完成后的的文件名
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // 字节类型的分块方式
        header('Accept-Ranges: bytes');

        // 传输编码为分块方式
        header('Content-Transfer-Encoding: chunked');

        // 避免和 Content-Transfer-Encoding: chunked 同时使用
        // header('Content-Length: ' . $filesize);

        // 文件总大小
        header('Accept-Length: ' . $filesize);

        // 禁用浏览器缓存控制
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); // HTTP 1.1
        header('Pragma: public'); // HTTP 1.0

        // 设置脚本永不超时
        set_time_limit(0);

        // 分块输出，每块大小为4096字节（针对大文件）
        $chunk = 4096;

        // 已读取的字节数
        $buffer = 0;

        // 循环处理
        while (!feof($handle) && $buffer < $filesize) {
            echo fread($handle, $chunk);
            $buffer += $chunk;
            //ob_flush();
            //flush();
        }

        // 关闭文件句柄
        fclose($handle);
    }

    /**
     * 是否是搜索引擎爬虫发起的请求
     *
     * @return bool
     */
    public static function isRobot()
    {
        static $isRobot;
        if (is_bool($isRobot)) {
            return $isRobot;
        }

        $isRobot   = false;
        $httpAgent = $_SERVER['HTTP_USER_AGENT'];
        $spiders   = 'Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla|alexa';
        $browsers  = 'MSIE|Netscape|Opera|Konqueror|Mozilla';
        if (!Str::contains($httpAgent, ['http://', 'https://']) && preg_match("/($browsers)/i", $httpAgent)) {
            $isRobot = false;
        } elseif (preg_match("/($spiders)/i", $httpAgent)) {
            $isRobot = true;
        }

        return $isRobot;
    }
}
