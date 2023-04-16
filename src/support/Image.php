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

use RuntimeException;

/**
 * 图片处理工具类
 * 
 * $result = Image::textWatermark('./08575554.jpg', '我市中国人人', Image::POSITION_RIGHT_BOTTOM);
 * var_dump($result);
 * var_dump(strtolower(IMAGETYPE_GIF));exit;
 * 此类需要GD库版本 v2.0.28+
 */
class Image
{
    /**
     * 位置常量
     */
    const POSITION_LEFT_TOP      = 1; // 左上
    const POSITION_CENTER_TOP    = 2; // 中上
    const POSITION_RIGHT_TOP     = 3; // 右上
    const POSITION_LEFT_MIDDLE   = 4; // 左中
    const POSITION_CENTER_MIDDLE = 5; // 中中（正中）
    const POSITION_RIGHT_MIDDLE  = 6; // 右中
    const POSITION_LEFT_BOTTOM   = 7; // 左下
    const POSITION_CENTER_BOTTOM = 8; // 中下
    const POSITION_RIGHT_BOTTOM  = 9; // 右下

	/**
	 * 创建缩略图
     * $thumbWidth 和 $thumbHeight 参数都为0时，缩略图按照原图的 1/5 等比例缩小
	 *
	 * @param string $imageFile 原图
	 * @param int $thumbWidth 缩略图宽度
	 * @param int $thumbHeight 缩略图高度
     * @param array $option
	 * @return string|false 成功时返回缩略图文件全路径地址，失败返回false
	 */
	public static function thumb(string $imageFile, int $thumbWidth = 0, int $thumbHeight = 0, array $option = [])
	{
        $config = array_merge([
            'image_thumb_location' => 0,           // 当提供的缩略宽高比不等于实际图片的宽高比时，实际缩小的图像在带有背景缩略图的位置，1为靠左或靠上，0位居中（默认），-1为靠右有靠下
            'image_thumb_bgcolor'  => '#ff0000',   // 背景色
            'image_thumb_bgalpha'  => 80,          // 背景色透明度，0-127，值越大越透明
            'image_jpeg_quality'   => 75,          // jpg图像质量，范围从 0（最差质量，文件更小）到 100（最佳质量，文件最大），默认为75
            'image_bmp_compressed' => true,        // bmp图片压缩
        ], $option);

        // 原图信息
        if (!$imageInfo = self::getImageInfo($imageFile)) {
            return false;
        }
        if (!$imageResource = self::getImageResource($imageFile, (string)$imageInfo['suffix'])) {
            return false;
        }
        $imageWidth  = $imageInfo['width'];
        $imageHeight = $imageInfo['height'];
        $imageSuffix = strtolower($imageInfo['suffix']);

        if ($thumbWidth < 0 || $thumbHeight < 0) {
            return false;
        }

        // 原图宽高比
        $imageRatio = $imageWidth / $imageHeight;

        $x = 0;
        $y = 0;

        if ($thumbWidth == 0 && $thumbHeight == 0) {
            // 缩略宽高都为0时，缩略图按照原图的 1/5 等比例缩小
            $thumbWidth = $imageWidth / 5;
            $thumbHeight = $imageHeight / 5;
        } elseif ($thumbWidth > 0 && $thumbHeight == 0) {
            // 只提供了缩略宽度参数，则缩略图高度按比例计算
            $thumbHeight = $thumbWidth / $imageRatio;
        } elseif ($thumbWidth == 0 && $thumbHeight > 0) {
            // 只提供了缩略高度参数，则缩略图宽度按比例计算
            $thumbWidth = $thumbHeight * $imageRatio;
        } else {
            // 宽高两个参数都提供的情况
            // 原图的宽高必须大于缩略图的宽高
            if ($imageWidth < $thumbWidth || $imageHeight < $thumbHeight) {
                return false;
            }

            // 缩略图宽高比
            $thumbRatio = $thumbWidth / $thumbHeight;

            // 计算图片裁剪后的大小
            $location = (int)$config['image_thumb_location'];
            if ($imageRatio > $thumbRatio) { // 原图比较宽，则缩略图高度按比例计算
                $bgColorFill = true;
                $thumbRealHeight = $thumbWidth / $imageRatio;
                switch ($location) {
                    case 1: // 靠上
                        $y = 0;
                        break;
                    case -1: // 靠下
                        $y = ceil($thumbHeight - $thumbRealHeight);
                        break;
                    case 0: // 垂直居中
                    default:
                        $y = floor(($thumbHeight - $thumbRealHeight) / 2);
                        break;
                }
            } elseif ($imageRatio < $thumbRatio) { // 原图比较高，则缩略图宽度按比例计算
                $bgColorFill = true;
                $thumbRealWidth = $thumbHeight * $imageRatio;
                switch ($location) {
                    case 1: // 靠左
                        $x = 0;
                        break;
                    case -1: // 靠右
                        $x = ceil($thumbWidth - $thumbRealWidth);
                        break;
                    case 0: // 水平居中
                    default:
                        $x = floor(($thumbWidth - $thumbRealWidth) / 2);
                        break;
                }
            }
        }

        // 图片缩小
        if (isset($bgColorFill)) {
            $rgb                = self::hex2Rgb((string)$config['image_thumb_bgcolor']);
            $thumbImageResource = imagecreatetruecolor($thumbWidth, $thumbHeight);
            $color              = imagecolorallocatealpha($thumbImageResource, $rgb['red'], $rgb['green'], $rgb['blue'], (int)$config['image_thumb_bgalpha']);
            imagefill($thumbImageResource, 0, 0, $color);
        } else {
            $thumbImageResource = imagecreatetruecolor($thumbWidth, $thumbHeight);
        }
        $thumbWidth  = $thumbRealWidth ?? $thumbWidth;
        $thumbHeight = $thumbRealHeight ?? $thumbHeight;
        imagecopyresampled($thumbImageResource, $imageResource, $x, $y, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight);

        // 创建缩略图
        $millisecond    = substr(str_pad(rtrim(explode(' ', microtime())[0], '0'), 4, mt_rand(0, 9)), 2, 4);
        $serialNumber   = date('YmdHis') . $millisecond . mt_rand(10, 99);
        $imageFileParts = pathinfo($imageFile);
        $thumbImageFile = $imageFileParts['dirname'] . DIRECTORY_SEPARATOR . $imageFileParts['filename'] . '_thumb_' . $serialNumber . '.' . $imageSuffix;
        if ($imageSuffix == 'jpg' || $imageSuffix == 'jpeg') {
            $result = imagejpeg($thumbImageResource, $thumbImageFile, (int)$config['image_jpeg_quality']);
        } elseif ($imageSuffix == 'bmp') {
            $result = imagebmp($thumbImageResource, $thumbImageFile, (bool)$config['image_bmp_compressed']);
        } else {
            $function = 'image' . $imageSuffix;
            $result = $function($thumbImageResource, $thumbImageFile);
        }

        // 释放图片资源
        imagedestroy($imageResource);
        imagedestroy($thumbImageResource);

        // 返回成功与否
        return $result ? realpath($thumbImageFile) : false;
	}

    /**
     * 原图加上文字水印
     * 中文字符串需要支持中文的字体
     *
     * @param string $imageFile 原图
     * @param string $watermarkText 水印文字
     * @param int $watermarkPosition 水印图位置
     * @param array $option
     * @return bool
     */
    public static function textWatermark(string $imageFile, string $watermarkText, int $watermarkPosition = self::POSITION_RIGHT_BOTTOM, array $option = [])
    {
        $config = array_merge([
            'image_text_fontcolor'  => '#333333',                                // 字体颜色
            'image_text_fontsize'   => 18,                                       // 字体尺寸
            'image_text_fontfamily' => __DIR__ . '/resource/fonts/simhei.ttf',   // 中文字符必须用支持中文的字体
            'image_text_fontangle'  => 0,                                        // 旋转角度
            'watermark_alpha'       => 50,                                       // 水印图透明度 0-127 值越大越透明
            'image_jpeg_quality'    => 75,                                       // jpg图像质量，范围从 0（最差质量，文件更小）到 100（最佳质量，文件最大），默认为75
            'image_bmp_compressed'  => true,                                     // bmp图片压缩
        ], $option);

        // 原图信息
        if (!$imageInfo = self::getImageInfo($imageFile)) {
            return false;
        }
        if (!$imageResource = self::getImageResource($imageFile, (string)$imageInfo['suffix'])) {
            return false;
        }
        $imageWidth  = $imageInfo['width'];
        $imageHeight = $imageInfo['height'];
        $imageSuffix = strtolower($imageInfo['suffix']);

        // 文字信息
        $fontColor  = (string)$config['image_text_fontcolor'];
        $fontSize   = (int)$config['image_text_fontsize'];
        $fontAngle  = (float)$config['image_text_fontangle'];
        $fontFamily = realpath($config['image_text_fontfamily']);

        // 水印位置
        $x = $imageWidth / 3;
        $y = $imageHeight / 3;
        if (function_exists('imagettfbbox')) {

            $ttfInfo = imagettfbbox($fontSize, $fontAngle, $fontFamily, $watermarkText);
            $ttfWidth  = abs($ttfInfo[2] - $ttfInfo[0]);
            $ttfHeight = abs($ttfInfo[5] - $ttfInfo[3]);
    
            // 原图的宽高必须大于文字的宽高
            if ($imageWidth < $ttfWidth || $imageHeight < $ttfHeight) {
                return false;
            }
    
            // 计算水印位置
            switch ($watermarkPosition) {
                    // 左上
                case self::POSITION_LEFT_TOP:
                    $x = 0;
                    $y = $ttfHeight;
                    break;
                    // 中上
                case self::POSITION_CENTER_TOP:
                    $x = floor(($imageWidth - $ttfWidth) / 2);
                    $y = $ttfHeight;
                    break;
                    // 右上
                case self::POSITION_RIGHT_TOP:
                    $x = $imageWidth - $ttfWidth;
                    $y = $ttfHeight;
                    break;
                    // 左中
                case self::POSITION_LEFT_MIDDLE:
                    $x = 0;
                    $y = floor(($imageHeight - $ttfHeight) / 2);
                    break;
                    // 中中（正中）
                case self::POSITION_CENTER_MIDDLE:
                    $x = floor(($imageWidth - $ttfWidth) / 2);
                    $y = floor(($imageHeight - $ttfHeight) / 2);
                    break;
                    // 右中
                case self::POSITION_RIGHT_MIDDLE:
                    $x = $imageWidth - $ttfWidth;
                    $y = floor(($imageHeight - $ttfHeight) / 2);
                    break;
                    // 左下
                case self::POSITION_LEFT_BOTTOM:
                    $x = 0;
                    $y = $imageHeight - $ttfHeight;
                    break;
                    // 中下
                case self::POSITION_CENTER_BOTTOM:
                    $x = floor(($imageWidth - $ttfWidth) / 2);
                    $y = $imageHeight - $ttfHeight;
                    break;
                    // 右下
                case self::POSITION_RIGHT_BOTTOM:
                    $x = $imageWidth - $ttfWidth;
                    $y = $imageHeight - $ttfHeight;
                    break;
                    // 随机位置
                default:
                    $x = mt_rand(0, $imageWidth - $ttfWidth);
                    $y = mt_rand(0, $imageHeight - $ttfHeight);
                    break;
            }
        }

        // 文字写入图片
        $rgb = self::hex2Rgb($fontColor);
        $color = imagecolorallocatealpha($imageResource, $rgb['red'], $rgb['green'], $rgb['blue'], (int)$config['watermark_alpha']);
        imagettftext($imageResource, $fontSize, $fontAngle, $x, $y, $color, $fontFamily, $watermarkText);

        // 合成并创建新图片
        if ($imageSuffix == 'jpg' || $imageSuffix == 'jpeg') {
            $result = imagejpeg($imageResource, $imageFile, (int)$config['image_jpeg_quality']);
        } elseif ($imageSuffix == 'bmp') {
            $result = imagebmp($imageResource, $imageFile, (bool)$config['image_bmp_compressed']);
        } else {
            $function = 'image' . $imageSuffix;
            $result = $function($imageResource, $imageFile);
        }

        // 释放图片资源
        imagedestroy($imageResource);

        // 返回成功与否
        return $result;
    }

    /**
     * 原图加上图片水印
     * 仅支持图片格式：GIF JPG JPEG PNG BMP
     * 原图的宽高必须大于水印图的宽高
     *
     * @param string $imageFile 原图
     * @param string $watermarkImageFile 水印图
     * @param int $watermarkPosition 水印图位置，默认右下，为0表示随机
     * @param array $option
     * @return bool
     */
    public static function imageWatermark(string $imageFile, string $watermarkImageFile, int $watermarkPosition = self::POSITION_RIGHT_BOTTOM, array $option = [])
    {
        $config = array_merge([
            'image_copy_merge'     => false,   // 合并模式，true时适用于全彩图片合成到目标图片，false时适用于透明背景的png图片合成到目标图片
            'watermark_alpha'      => 50,      // 水印图透明度 0-100 值越大越透明
            'image_jpeg_quality'   => 75,      // jpg图像质量，范围从 0（最差质量，文件更小）到 100（最佳质量，文件最大），默认为75
            'image_bmp_compressed' => true,    // bmp图片压缩
        ], $option);

        // 原图信息
        if (!$imageInfo = self::getImageInfo($imageFile)) {
            return false;
        }
        if (!$imageResource = self::getImageResource($imageFile, (string)$imageInfo['suffix'])) {
            return false;
        }
        $imageWidth  = $imageInfo['width'];
        $imageHeight = $imageInfo['height'];
        $imageSuffix = strtolower($imageInfo['suffix']);

        // 水印图信息
        if (!$watermarkImageInfo = self::getImageInfo($watermarkImageFile)) {
            return false;
        }
        if (!$watermarkImageResource = self::getImageResource($watermarkImageFile, (string)$watermarkImageInfo['suffix'])) {
            return false;
        }
        $watermarkImageWidth  = $watermarkImageInfo['width'];
        $watermarkImageHeight = $watermarkImageInfo['height'];

        // 原图的宽高必须大于水印图的宽高
        if ($imageWidth < $watermarkImageWidth || $imageHeight < $watermarkImageHeight) {
            return false;
        }

        // 计算水印位置
        switch ($watermarkPosition) {
                // 左上
            case self::POSITION_LEFT_TOP:
                $x = 0;
                $y = 0;
                break;
                // 中上
            case self::POSITION_CENTER_TOP:
                $x = floor(($imageWidth - $watermarkImageWidth) / 2);
                $y = 0;
                break;
                // 右上
            case self::POSITION_RIGHT_TOP:
                $x = $imageWidth - $watermarkImageWidth;
                $y = 0;
                break;
                // 左中
            case self::POSITION_LEFT_MIDDLE:
                $x = 0;
                $y = floor(($imageHeight - $watermarkImageHeight) / 2);
                break;
                // 中中（正中）
            case self::POSITION_CENTER_MIDDLE:
                $x = floor(($imageWidth - $watermarkImageWidth) / 2);
                $y = floor(($imageHeight - $watermarkImageHeight) / 2);
                break;
                // 右中
            case self::POSITION_RIGHT_MIDDLE:
                $x = $imageWidth - $watermarkImageWidth;
                $y = floor(($imageHeight - $watermarkImageHeight) / 2);
                break;
                // 左下
            case self::POSITION_LEFT_BOTTOM:
                $x = 0;
                $y = $imageHeight - $watermarkImageHeight;
                break;
                // 中下
            case self::POSITION_CENTER_BOTTOM:
                $x = floor(($imageWidth - $watermarkImageWidth) / 2);
                $y = $imageHeight - $watermarkImageHeight;
                break;
                // 右下
            case self::POSITION_RIGHT_BOTTOM:
                $x = $imageWidth - $watermarkImageWidth;
                $y = $imageHeight - $watermarkImageHeight;
                break;
                // 随机位置
            default:
                $x = mt_rand(0, $imageWidth - $watermarkImageWidth);
                $y = mt_rand(0, $imageHeight - $watermarkImageHeight);
                break;
        }

        // 如果是gif图片，必须将其采样到真彩色图像
        if ($imageSuffix == 'gif') {
            $gifImageResource = imagecreatetruecolor($imageWidth, $imageHeight);
            imagecopy($gifImageResource, $imageResource, 0, 0, 0, 0, $imageWidth, $imageHeight);
            $imageResource = $gifImageResource;
        }

        // 添加水印
        if ((bool)$config['image_copy_merge']) {
            imagecopymerge($imageResource, $watermarkImageResource, $x, $y, 0, 0, $watermarkImageWidth, $watermarkImageHeight, (int)$config['watermark_alpha']);
        } else {
            imagealphablending($imageResource, true); // 混色模式
            imagecopy($imageResource, $watermarkImageResource, $x, $y, 0, 0, $watermarkImageWidth, $watermarkImageHeight);
        }

        // 合成并创建新图片
        if ($imageSuffix == 'jpg' || $imageSuffix == 'jpeg') {
            $result = imagejpeg($imageResource, $imageFile, (int)$config['image_jpeg_quality']);
        } elseif ($imageSuffix == 'bmp') {
            $result = imagebmp($imageResource, $imageFile, (bool)$config['image_bmp_compressed']);
        } else {
            $function = 'image' . $imageSuffix;
            $result = $function($imageResource, $imageFile);
        }

        // 释放图片资源
        imagedestroy($imageResource);
        imagedestroy($watermarkImageResource);

        // 返回成功与否
        return $result;
    }

    /**
     * 创建验证码
     * 返回由验证码字符串和验证图片base64编码字符串组成的数组
     *
     * @param array $option
     * @return array
     */
    public static function captcha(array $option = [])
    {
        if (is_int($option)) {
            $num = $option;
            $option = [];
            $option['num'] = $num;
        }
        $config = array_merge([
            'num'         => 4,                                        // 验证码字符数
            'width'       => 180,                                      // 验证码图片宽度
            'height'      => 60,                                       // 验证码图片高度
            'fontsize'    => 48,                                       // 验证码字符的字体尺寸
            'fontfamily'  => __DIR__ . '/resource/fonts/simhei.ttf',   // 验证码字符的字体
            'noise_num'   => 80,                                       // 验证码图片的杂点数量
            'curve_num'   => 10,                                       // 验证码图片的杂线数量
            'bgcolor'     => '#f8f8f8',                                // 验证码图片的背景颜色
            'use'         => 'en',                                     // 使用英文验证码还是中文验证码，取值：en或zh
            'custom_dict' => '',                                       // 自定义的验证码字符，如果设置该值则num等属性无效，可通过use参数设置为zh来调节显示字符串的左边距
        ], $option);

        $num        = $config['num'];
        $width      = $config['width'];
        $height     = $config['height'];
        $fontsize   = $config['fontsize'];
        $fontfamily = $config['fontfamily'];
        $noiseNum   = $config['noise_num'];
        $curveNum   = $config['curve_num'];
        
        // 创建画布
        $rgb = self::hex2Rgb((string)$config['bgcolor']);
        $resource = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($width, $height) : imagecreate($width, $height);
        imagefill($resource, 0, 0, imagecolorallocate($resource, $rgb['red'], $rgb['green'], $rgb['blue']));
        
        // 画干扰点
        $noiseSet = '0123456789abcdefghijklmnopqrstuvwxyz';
        $noiseSetCnt = strlen($noiseSet) - 1;
        for ($i = 0; $i < $noiseNum; $i++) {
            $color = imagecolorallocatealpha($resource, mt_rand(100, 225), mt_rand(100, 225), mt_rand(100, 225), mt_rand(0, 30));
            imagestring($resource, mt_rand(1, 5), mt_rand(-5, $width - 5), mt_rand(-5, $height - 5), $noiseSet[mt_rand(0, $noiseSetCnt)], $color);
        }
        
        // 画干扰线
        for ($i = 0; $i < $curveNum; $i++) {
            $color = imagecolorallocate($resource, mt_rand(175, 225), mt_rand(175, 225), mt_rand(175, 225));
            imageline($resource, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
        }

        // 验证码字符字典
        if (empty($config['custom_dict'])) {
            $dicts = [
                'en' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', //
                'zh' => '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借',
            ];
    
            if ($config['use'] == 'zh') {
                $dict = $dicts['zh'];
                $cnt = mb_strlen($dict, 'utf-8') - 1;
            } else {
                $dict = $dicts['en'];
                $cnt = strlen($dict) - 1;
            }
        
            // 画字符
            $chars = '';
            $x     = 0;
            for ($i = 0; $i < $num; $i++) {
                $angle = mt_rand(-20, 20);
                if ($config['use'] == 'zh') {
                    $char = iconv_substr($dict, floor(mt_rand(0, $cnt)), 1, 'utf-8');
                    $x = $fontsize * ($i + 1) * 1.5;
                    $y = $fontsize + mt_rand(10, 20);
                } else {
                    $char = $dict[mt_rand(0, $cnt)];
                    if (function_exists('imagettfbbox')) {
                        $charInfo = imagettfbbox($fontsize, $angle, $fontfamily, $char);
                        $charWidth  = abs($charInfo[2] - $charInfo[0]);
                        $charHeight = abs($charInfo[5] - $charInfo[3]);
                        $x += $charWidth * 1.1;
                        $y = $charHeight * 1.1;
                    } else {
                        $x += mt_rand($fontsize * 1.2, $fontsize * 1.6);
                        $y = $fontsize * 1.6;
                    }
                }
                $chars .= $char;
                $color = imagecolorallocatealpha($resource, mt_rand(1, 80), mt_rand(1, 80), mt_rand(1, 80), mt_rand(40, 70));
                imagettftext($resource, $fontsize, $angle, $x, $y, $color, $fontfamily, $char);
            }
        } else {
            $angle = mt_rand(-5, 5);
            $chars = (string)$config['custom_dict'];
            if ($config['use'] == 'zh') {
                $x = $fontsize * 1.5;
                $y = $fontsize + mt_rand(10, 20);
            } else {
                if (function_exists('imagettfbbox')) {
                    $charInfo = imagettfbbox($fontsize, $angle, $fontfamily, $chars);
                    $charWidth  = abs($charInfo[2] - $charInfo[0]);
                    $charHeight = abs($charInfo[5] - $charInfo[3]);
                    $x = $charWidth * 1.1;
                    $y = $charHeight * 1.1;
                } else {
                    $x = mt_rand($fontsize * 1.2, $fontsize * 1.6);
                    $y = $fontsize * 1.6;
                }
            }

            $color = imagecolorallocatealpha($resource, mt_rand(1, 80), mt_rand(1, 80), mt_rand(1, 80), mt_rand(40, 70));
            imagettftext($resource, $fontsize, $angle, $x, $y, $color, $fontfamily, $chars);
        }

        ob_start();
        imagepng($resource);
        imagedestroy($resource);
        $content     = ob_get_clean();
        $contentType = image_type_to_mime_type(IMAGETYPE_PNG);

        // 输出验证图片base64编码等数据
        return [
            'type'      => $contentType,
            'chars'     => $chars,
            'base64str' => 'data:'. $contentType . ';base64,' . base64_encode($content),
        ];
    }

    /**
     * 创建自定义验证码
     *
     * @param string $customDictStr
     * @param array $option
     * @return array
     */
    public static function captchaCustom(string $customDictStr, array $option = [])
    {
        $config = array_merge([
            'custom_dict' => $customDictStr,
        ], $option);
        return self::captcha($config);
    }

    /**
     * 获取图片资源的标识符
     *
     * @param string $imageFile
     * @param string $imageSuffix
     * @return resource|false
     */
    public static function getImageResource(string $imageFile, string $imageSuffix = '')
    {
        $imageFile = self::getRealFile($imageFile);

        if (empty($imageSuffix)) {
            $imageSuffix = pathinfo($imageSuffix, PATHINFO_EXTENSION);
        }

        $imageSuffix = strtolower($imageSuffix);
        if ($imageSuffix == 'jpg') {
            $imageSuffix = 'jpeg';
        }

        if (!in_array($imageSuffix, ['gif', 'jpeg', 'png', 'bmp'])) {
            return false;
        }

        $function = 'imagecreatefrom' . $imageSuffix;
        return $function($imageFile);
    }

    /**
     * 获取图片信息
     * 此方法不需要GD图像库支持
     *
     * @param string $imageFile
     * @return array|false
     */
    public static function getImageInfo(string $imageFile)
    {
        $imageFile = self::getRealFile($imageFile);

        if (false === ($imageInfo = getimagesize($imageFile))) {
            return false;
        }

        // 图片类型获取错误
        // 此段代码可以注释，意义不大
        if ((int)$imageInfo[2] <= 0) {
            if (function_exists('exif_imagetype')) {
                $imageInfo[2] = exif_imagetype($imageFile);
            }
        }

        list($width, $height, $type, $attr) = $imageInfo;

        $channels = $imageInfo['channels'] ?? null;
        $bits     = $imageInfo['bits'] ?? null;
        $mime     = $imageInfo['mime'] ?? '';

        // 常用图片后缀
        switch ($type) {
            case \IMAGETYPE_GIF:
                $suffix = 'gif';
                break;
            case \IMAGETYPE_PNG:
                $suffix = 'png';
                break;
            case \IMAGETYPE_BMP:
                $suffix = 'bmp';
                break;
            case \IMAGETYPE_JPEG:
                $suffix = 'jpg';
                break;
            default:
                $suffix = '';
                break;
        }

        $info = [
            'width'    => $width,
            'height'   => $height,
            'type'     => $type,
            'attr'     => $attr,
            'channels' => $channels,
            'bits'     => $bits,
            'mime'     => $mime,
            'suffix'   => $suffix,
        ];

        return $info;
    }

    /**
     * 判断给出的JPG图片是否是RGB图像
     *
     * @param string $imageFile
     * @return bool
     */
    public static function isRgbJpgImage($imageFile)
    {
        $imageInfo = self::getImageInfo($imageFile);
        return $imageInfo['channels'] == 3;
    }

    /**
     * 判断给出的JPG图片是否是CMYK图像
     *
     * @param string $imageFile
     * @return bool
     */
    public static function isCmykJpgImage($imageFile)
    {
        $imageInfo = self::getImageInfo($imageFile);
        return in_array($imageInfo['channels'], [4, 8]);
    }

    /**
     * 获取JPG图片的位数
     *
     * @param string $imageFile
     * @return array|null
     */
    public static function getBitsFromJpgImage($imageFile)
    {
        $imageInfo = self::getImageInfo($imageFile);
        return $imageInfo['type'] == \IMAGETYPE_JPEG ? $imageInfo['bits'] : null;
    }

    /**
     * 检测扩展是否加载
     *
     * @param string $extension
     * @return void
     * @throws RuntimeExcetion
     */
    public static function checkExtension(string $extension)
    {
        if (!extension_loaded($extension)) {
            throw new RuntimeException($extension . ' extension not enabled', 1);
        }
    }

    /**
     * 检测图片文件是否是一个有效的文件，返回一个实际物理地址
     *
     * @param string $imageFile
     * @return string
     * @throws RuntimeException
     */
    public static function getRealFile($imageFile)
    {
        if (!is_file($imageFile)) {
            throw new RuntimeException($imageFile . ' is an invalid file', 1);
        }
        return realpath($imageFile);
    }

    /**
     * RGB转十六进制颜色值
     *
     * @param string $rgb RGB颜色的字符串 如：rgb(255,255,255)
     * @return string 如：#FFFFFF
     */
    public static function rgb2Hex($rgb)
    {
        $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        preg_match($regexp, $rgb, $match);
        array_shift($match);
        $hexColor = '#';
        $hex      = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        for ($i = 0; $i < 3; $i++) {
            $r     = null;
            $c     = $match[$i];
            $hexAr = [];
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            $ret  = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }

    /**
     * 十六进制颜色值转RGB
     *
     * @param string $hexColor
     * @return array
     */
    public static function hex2Rgb($hexColor)
    {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = [
                'red'   => hexdec(substr($color, 0, 2)),
                'green' => hexdec(substr($color, 2, 2)),
                'blue'  => hexdec(substr($color, 4, 2)),
            ];
        } else {
            $color = $hexColor;
            $red   = substr($color, 0, 1) . substr($color, 0, 1);
            $green = substr($color, 1, 1) . substr($color, 1, 1);
            $blue  = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = [
                'red'   => hexdec($red),
                'green' => hexdec($green),
                'blue'  => hexdec($blue),
            ];
        }
        return $rgb;
    }
}
