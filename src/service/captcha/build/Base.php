<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\support\captcha\build;

use funsent\kernel\config\Config;
use funsent\kernel\request\Request;
use funsent\kernel\session\Session;

/**
 * 验证码服务实现
 * @package funsent\support\captcha\build
 */
class Base
{
    /**
     * 资源
     * @var resource
     */
    private $img;

    /**
     * 验证字符
     * @var string
     */
    private $str;

    /**
     * 随机种子
     * @var string
     */
    private $randomStr = '23456789abcdefghjkmnpqrstuvwsyz';

    /**
     * 画布宽度
     * @var integer
     */
    private $width = 100;

    /**
     * 画布高度
     * @var integer
     */
    private $height = 30;

    /**
     * 背景颜色
     * @var string
     */
    private $bgColor = '#ffffff';

    /**
     * 字符个数
     * @var integer
     */
    private $num = 4;

    /**
     * 字体
     * @var string
     */
    private $font = '';

    /**
     * 字体大小
     * @var integer
     */
    private $fontSize = 16;

    /**
     * 字体颜色
     * @var string
     */
    private $fontColor = '';

    /**
     * 构造方法
     * 初始化配置
     */
    public function __construct()
    {
        foreach ((array)Config::get('captcha') as $key => $value) {
            $this->$key = $value;
        }
        $this->font = __DIR__ . '/font.ttf';
    }

    /**
     * 调用不存在的实例方法时触发，设置属性值
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->$method = current($parameters);
        return $this;
    }

    /**
     * 创建验证码
     * @return boolean|void
     */
    public function make()
    {
        $this->create();
        if (!Request::isCli()) {
            header('Content-type:image/png');
            imagepng($this->img);
            imagedestroy($this->img);
            exit;
        }
        return true;
    }

    /**
     * 验证
     * @param string $field 表单字段
     * @return boolean
     */
    public function auth($field = 'captcha')
    {
        $captcha = $this->get();
        Session::delete('captcha');
        return strtoupper(Request::post($field)) == strtoupper($captcha);
    }

    /**
     * 获取验证字符
     * @return string
     */
    public function get()
    {
        return Session::get('captcha');
    }

    /**
     * 生成验证字符
     * @return mixed
     */
    private function getStr()
    {
        $str = '';
        for ($i = 0; $i < $this->num; $i++) {
            $str .= $this->randomStr[mt_rand(0, strlen($this->randomStr) - 1)];
        }
        return $this->str = $str;
    }

    /**
     * 创建画布
     * @return void
     */
    private function create()
    {
        if (!$this->checkGd()) {
            return false;
        }
        $width = $this->width;
        $height = $this->height;
        $bgColor = $this->bgColor;
        $img = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($img, hexdec(substr($bgColor, 1, 2)), hexdec(substr($bgColor, 3, 2)), hexdec(substr($bgColor, 5, 2)));
        imagefill($img, 0, 0, $bgColor);
        $this->img = $img;
        $this->createLine();
        $this->createStr();
        $this->createPixel();
        $this->createRectangle();
    }

    /**
     * 画线
     * @return void
     */
    private function createLine()
    {
        $width = $this->width;
        $height = $this->height;
        $lineColor = '#dcdcdc';
        $color = imagecolorallocate($this->img, hexdec(substr($lineColor, 1, 2)), hexdec(substr($lineColor, 3, 2)), hexdec(substr($lineColor, 5, 2)));
        $cnt = $height / 5;
        for ($i = 1; $i < $cnt; $i++) {
            $step = $i * 5;
            imageline($this->img, 0, $step, $width, $step, $color);
        }
        $cnt = $width / 10;
        for ($i = 1; $i < $cnt; $i++) {
            $step = $i * 10;
            imageline($this->img, $step, 0, $step, $height, $color);
        }
    }

    /**
     * 画矩形边框
     * @return void
     */
    private function createRectangle()
    {
        //imagerectangle($this->img, 0, 0, $this->width - 1,$this->height - 1, $this->fontColor);
    }

    /**
     * 画验证字符
     * @return void
     */
    private function createStr()
    {
        $str = $this->getStr();
        $color = $this->fontColor;
        if (!empty($color)) {
            $fontColor = imagecolorallocate($this->img, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
        }
        $x = ($this->width - 10) / $this->num;
        for ($i = 0; $i < $this->num; $i++) {
            if (empty($color)) {
                $fontColor = imagecolorallocate($this->img, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
            }
            imagettftext(
                $this->img,
                $this->fontSize,
                mt_rand(-30, 30),
                $x * $i + mt_rand(6, 10),
                mt_rand($this->height / 1.3, $this->height - 5),
                $fontColor,
                $this->font,
                $str[$i]
            );
        }
        $this->fontColor = $fontColor;
        Session::set('captcha', $str);
    }

    /**
     * 画点
     * @return void
     */
    private function createPixel()
    {
        $pixelColor = $this->fontColor;
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel(
                $this->img,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $pixelColor
            );
        }
        for ($i = 0; $i < 2; $i++) {
            imageline(
                $this->img,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $pixelColor
            );
        }
        // 画圆弧
        for ($i = 0; $i < 1; $i++) {
            imagearc(
                $this->img,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, 160),
                mt_rand(0, 200),
                $pixelColor
            );
        }
        imagesetthickness($this->img, 1);
    }

    /**
     * 检查GD库
     * @return boolean
     */
    private function checkGd()
    {
        return extension_loaded('gd') && function_exists('imagepng');
    }
}
