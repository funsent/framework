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

namespace funsent\support;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\Generator\CombGenerator;
use Ramsey\Uuid\Codec\TimestampFirstCombCodec;

/**
 * 字符串工具类
 */
class Str
{
	// use Macroable;

    /**
     * 字符串类型
     */
    const STR_TYPE_ALPHA           = 1; // 大小写字母
    const STR_TYPE_ALPHA_UPPERCASE = 2; // 大写字母
    const STR_TYPE_ALPHA_LOWERCASE = 3; // 小写字母
    const STR_TYPE_ALPHA_NUM       = 4; // 大小写字母和数字
    const STR_TYPE_ALPHA_DASH      = 5; // 大小写字母和数字，以及破折号和下划线
    const STR_TYPE_NUMERIC         = 6; // 数字
    const STR_TYPE_CHINESE         = 7; // 汉字

    /**
     * 字符串全角半角
     */
    const STR_CASE_DBC             = 1;
    const STR_CASE_SBC             = 2;
    
    /**
     * camel-cased 风格字符串缓存
     * 
     * @var array
     */
    protected static $camelCache = [];

    /**
     * snake-cased 风格字符串缓存
     * 
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * studly-cased 风格字符串缓存
     * 
     * @var array
     */
    protected static $studlyCache = [];
	
	/**
	 * 判断字符串是否在另一个字符串中
     * 
	 * @param string $needle
	 * @param string $haystack
	 * @return bool
	 */
    public static function exist($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * 将UTF-8值为ASCI值
     * 
     * @param string $value
     * @param string $language
     * @return string
     */
    public static function ascii($value, $language = 'en')
    {
        $languageSpecific = static::languageSpecificCharsArray($language);
        if (!is_null($languageSpecific)) {
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
        }
        foreach (static::charsArray() as $key => $val) {
            $value = str_replace($val, $key, $value);
        }
        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }

    /**
     * 在给定值之前获取字符串的前面部分
     * 
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function before($subject, $search)
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    /**
     * 在给定值之后返回字符串的剩余部分
     * 
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * 将字符串转化为驼峰形式
     * 
     * @param string $value
     * @return string
     */
    public static function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }
        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * 确定给定字符串是否包含子字符串
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 确定给定字符串是否包含所有数组值
     * 
     * @param string $haystack
     * @param array $needles
     * @return bool
     */
    public static function containsAll($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (!static::contains($haystack, $needle)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 添加字符到字符串结尾
     * 
     * @param string $value
     * @param string $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');
        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * 确定给定字符串是否与给定模式匹配
     * 
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $patterns = Arr::wrap($pattern);
        if (empty($patterns)) {
            return false;
        }
        foreach ($patterns as $pattern) {
            if ($pattern == $value) {
                return true;
            }
            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);
            if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 返回给定字符串的长度
     * 
     * @param string $value
     * @param string $encoding
     * @return int
     */
    public static function length($value, $encoding = null)
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }
        return mb_strlen($value);
    }

    /**
     * 限制输出字符串的数目，该方法接收一个字符串作为第一个参数以及该字符串最大输出字符数作为第二个参数
     * 例如：Str::limit('The PHP framework for web artisans.', 7);
     * 输出：The PHP...
     * 
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * 限制字符串中的单词数
     * 
     * @param string $value
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);
        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }
        return rtrim($matches[0]) . $end;
    }

    /**
     * 将 Class@method 样式回调解析为类和方法
     * 
     * @param string $callback
     * @param string|null $default
     * @return array
     */
    public static function parseCallback($callback, $default = null)
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * 获得英语单词的单数形式
     * 
     * @param string $value
     * @return string
     */
    public static function singular($value)
    {
        return Pluralizer::singular($value);
    }

    /**
     * 获得英语单词的复数形式
     * 
     * @param string $value
     * @param int $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        return Pluralizer::plural($value, $count);
    }

    /**
     * 将英语字符串的最后一个单词复数化，单词开头字母大写
     * 
     * @param string $value
     * @param int $count
     * @return string
     */
    public static function pluralStudly($value, $count = 2)
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lastWord = array_pop($parts);
        return implode('', $parts) . self::plural($lastWord, $count);
    }

    /**
     * 生成更真实的"随机的"字母数字字符串
     * 
     * @param int $length
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

	/**
	 * 生成指定大小范围内基于时间种子的随机数字
	 *
	 * @param int $min 最小数字
	 * @param int $max 最大数字
	 * @return int
	 */
	public static function randomNumber($min, $max = null)
	{
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float) $sec + ((float) $usec * 100000);

        if (empty($max)) {
            $max = mt_getrandmax();
        }

        mt_srand($seed);
		return mt_rand($min, $max);
	}

    /**
     * 生成指定大小范围内的随机浮点数
     *
     * @param int $min
     * @param int $max
     * @param int $precision
     * @return float
     */
    public static function randomFloat($min = 0, $max = 1, $precision = 12)
    {
        $float = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return number_format($float, $precision, '.', '');
    }

    /**
     * 生成随机字符串，支持汉字
     * 
     * @param int $length
     * @param int $strType
     * @param string $suffix 后缀
     * @return string
     */
    public static function randomize($length = 16, $strType = self::STR_TYPE_ALPHA_NUM, $suffix = '')
    {
        $stringSets = [
            1 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
            2 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            3 => 'abcdefghijklmnopqrstuvwxyz',
            4 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789',
            5 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789-_',
            6 => '0123456789',
            7 => '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借',
        ];

        $chars = $stringSets[$strType] . $suffix;
        if ($length > 10) {
            $chars = str_repeat($chars, $length);
        }

        $string = '';

        // 汉字
        if ($strType == self::STR_TYPE_CHINESE) {
            for ($i = 0; $i < $length; $i++) {
                $string .= mb_substr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8');
            }
            return $string;
        }

        // 非汉字
        for ($i = 0; $i < $length; $i++) {
            $index = mt_rand(0, strlen($chars) - 1);
            $string .= $chars[$index];
        }
        return str_shuffle($string);
    }

    /**
     * 用数组顺序替换字符串中的给定值
     * 
     * @param string $search
     * @param array $replace
     * @param string $subject
     * @return string
     */
    public static function replaceArray($search, array $replace, $subject)
    {
        $segments = explode($search, $subject);
        $result = array_shift($segments);
        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search) . $segment;
        }
        return $result;
    }

    /**
     * 替换字符串中给定值的第一次出现
     * 
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        if ($search == '') {
            return $subject;
        }
        $position = strpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    /**
     * 替换字符串中给定值的最后一次出现
     * 
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $position = strrpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    /**
     * 以给定值的单个实例开始字符串
     * 
     * @param string $value
     * @param string $prefix
     * @return string
     */
    public static function start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');
        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }

    /**
     * 将给定的字符串转换为小写
     * 
     * @param string $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 将给定字符串设置为大写形式
     * 
     * @param string $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 将给定的字符串转换为指定大小写版本标题
     * 
     * @param string $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * 将给定字符串生成URL友好的格式
     * 例如：Str::slug('Funsent 1 Framework')
     * 输出：funsent-1-framework
     * 
     * @param string $title
     * @param string $separator
     * @param string|null $language
     * @return string
     */
    public static function slug($title, $separator = '-', $language = 'en')
    {
        $title = $language ? static::ascii($title, $language) : $title;
        $flip = $separator === '-' ? '_' : '-'; // 将所有破折号/下划线转换为分隔符
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
        $title = str_replace('@', $separator . 'at' . $separator, $title); // 将@改为at
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', static::lower($title)); // 删除所有不是分隔符、字母、数字或空格的字符
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title); // 通过单个分隔符替换所有分隔符字符和空格
        return trim($title, $separator);
    }

    /**
     * 将字符串转换为 kebab case 风格形式（-分割单词的形式）
     * 例如：Str::kebab(''fooBar'')
     * 输出：foo-bar
     * @param string $value
     * @return string
     */
    public static function kebab($value)
    {
        return static::snake($value, '-');
    }

    /**
     * 将给定字符串转化为下划线分隔的字符串
     * 例如：Str::snake(''fooBar'')
     * 输出：foo_bar
     * 
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value;
        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }
        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * 确定给定字符串是否以给定的子字符串开头
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 确定给定字符串是否以给定子字符串结尾
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 首字母大写，并去除-和_
     * 例如：Str::studly('foo_bar')
     * 输出：FooBar
     * 
     * @param string $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    /**
     * 返回由开始参数和长度参数指定的字符串部分
     * 
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * 截取字符串，支持中文和其他编码
     * 
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @param string $charset
     * @param string $suffix
     * @return string
     */
    public static function truncate($string, $start = 0, $length = null, $charset = 'utf-8', $suffix = '')
    {
        if (function_exists('mb_substr')) {
            $slice = mb_substr($string, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $length = empty($length) ? iconv_strlen($string, $charset) : $length;
            $slice = iconv_substr($string, $start, $length, $charset);
        } else {
            $encodings = [
                'utf-8'  => "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/",
                'gb2312' => "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/",
                'gbk'    => "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/",
                'big5'   => "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/",
            ];
            preg_match_all($encodings[$charset], $string, $match);
            $slice = implode('', array_slice($match[0], $start, $length));
        }
        return $slice . $suffix;
    }

    /**
     * 字符串的第一个大写字母
     * 
     * @param string $string
     * @return string
     */
    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * 生成32位唯一标识
     * 
     * @param mixed $var
     * @return string
     */
    public static function guid($var)
    {
        if (is_object($var)) {
            return spl_object_hash($var);
        } elseif (is_resource($var)) {
            $var = get_resource_type($var) . strval($var);
        } else {
            $var = serialize($var);
        }
        return md5($var);
    }

    /**
     * 生成UUID (version 4)
     * 
     * @return \Ramsey\Uuid\UuidInterface
     */
    public static function uuid()
    {
        return Uuid::uuid4();
    }

    /**
     * 生成时间排序的UUID (version 4)
     * 
     * @return \Ramsey\Uuid\UuidInterface
     */
    public static function orderedUuid()
    {
        $factory = new UuidFactory;
        $factory->setRandomGenerator(new CombGenerator(
            $factory->getRandomGenerator(),
            $factory->getNumberConverter()
        ));
        $factory->setCodec(new TimestampFirstCombCodec(
            $factory->getUuidBuilder()
        ));
        return $factory->uuid4();
    }

    /**
     * 返回ascii方法的替换
     * Note: Adapted from Stringy\Stringy.
     * 
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     * @return array
     */
    protected static function charsArray()
    {
        static $charsArray;

        if (isset($charsArray)) {
            return $charsArray;
        }

        return $charsArray = [
            '0'    => ['°', '₀', '۰', '０'],
            '1'    => ['¹', '₁', '۱', '１'],
            '2'    => ['²', '₂', '۲', '２'],
            '3'    => ['³', '₃', '۳', '３'],
            '4'    => ['⁴', '₄', '۴', '٤', '４'],
            '5'    => ['⁵', '₅', '۵', '٥', '５'],
            '6'    => ['⁶', '₆', '۶', '٦', '６'],
            '7'    => ['⁷', '₇', '۷', '７'],
            '8'    => ['⁸', '₈', '۸', '８'],
            '9'    => ['⁹', '₉', '۹', '９'],
            'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä', 'א'],
            'b'    => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ', 'ב'],
            'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
            'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ', 'ד'],
            'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
            'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ', 'פ', 'ף'],
            'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ', 'ג'],
            'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ', 'ה'],
            'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ', 'י'],
            'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
            'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ', 'ק'],
            'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ', 'ל'],
            'm'    => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ', 'מ', 'ם'],
            'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ', 'נ'],
            'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
            'p'    => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ', 'פ', 'ף'],
            'q'    => ['ყ', 'ｑ'],
            'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ', 'ר'],
            's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ', 'ס'],
            't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ', 'ת'],
            'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
            'v'    => ['в', 'ვ', 'ϐ', 'ｖ', 'ו'],
            'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
            'x'    => ['χ', 'ξ', 'ｘ'],
            'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
            'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ', 'ז'],
            'aa'   => ['ع', 'आ', 'آ'],
            'ae'   => ['æ', 'ǽ'],
            'ai'   => ['ऐ'],
            'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'   => ['ђ', 'đ'],
            'dz'   => ['џ', 'ძ', 'דז'],
            'ei'   => ['ऍ'],
            'gh'   => ['غ', 'ღ'],
            'ii'   => ['ई'],
            'ij'   => ['ĳ'],
            'kh'   => ['х', 'خ', 'ხ'],
            'lj'   => ['љ'],
            'nj'   => ['њ'],
            'oe'   => ['ö', 'œ', 'ؤ'],
            'oi'   => ['ऑ'],
            'oii'  => ['ऒ'],
            'ps'   => ['ψ'],
            'sh'   => ['ш', 'შ', 'ش', 'ש'],
            'shch' => ['щ'],
            'ss'   => ['ß'],
            'sx'   => ['ŝ'],
            'th'   => ['þ', 'ϑ', 'θ', 'ث', 'ذ', 'ظ'],
            'ts'   => ['ц', 'ც', 'წ'],
            'ue'   => ['ü'],
            'uu'   => ['ऊ'],
            'ya'   => ['я'],
            'yu'   => ['ю'],
            'zh'   => ['ж', 'ჟ', 'ژ'],
            '(c)'  => ['©'],
            'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
            'B'    => ['Б', 'Β', 'ब', 'Ｂ'],
            'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
            'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
            'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
            'F'    => ['Ф', 'Φ', 'Ｆ'],
            'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
            'H'    => ['Η', 'Ή', 'Ħ', 'Ｈ'],
            'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
            'J'    => ['Ｊ'],
            'K'    => ['К', 'Κ', 'Ｋ'],
            'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
            'M'    => ['М', 'Μ', 'Ｍ'],
            'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
            'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
            'P'    => ['П', 'Π', 'Ｐ'],
            'Q'    => ['Ｑ'],
            'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
            'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
            'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
            'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
            'V'    => ['В', 'Ｖ'],
            'W'    => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
            'X'    => ['Χ', 'Ξ', 'Ｘ'],
            'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
            'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
            'AE'   => ['Æ', 'Ǽ'],
            'Ch'   => ['Ч'],
            'Dj'   => ['Ђ'],
            'Dz'   => ['Џ'],
            'Gx'   => ['Ĝ'],
            'Hx'   => ['Ĥ'],
            'Ij'   => ['Ĳ'],
            'Jx'   => ['Ĵ'],
            'Kh'   => ['Х'],
            'Lj'   => ['Љ'],
            'Nj'   => ['Њ'],
            'Oe'   => ['Œ'],
            'Ps'   => ['Ψ'],
            'Sh'   => ['Ш', 'ש'],
            'Shch' => ['Щ'],
            'Ss'   => ['ẞ'],
            'Th'   => ['Þ', 'Θ', 'ת'],
            'Ts'   => ['Ц'],
            'Ya'   => ['Я', 'יא'],
            'Yu'   => ['Ю', 'יו'],
            'Zh'   => ['Ж'],
            ' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
        ];
    }

    /**
     * 返回ascii方法的特定语言替换
     * Note: Adapted from Stringy\Stringy.
     * 
     * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
     * @param string $language
     * @return array|null
     */
    protected static function languageSpecificCharsArray($language)
    {
        static $languageSpecific;

        if (!isset($languageSpecific)) {
            $languageSpecific = [
                'bg' => [
                    ['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
                    ['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
                ],
                'da' => [
                    ['æ', 'ø', 'å', 'Æ', 'Ø', 'Å'],
                    ['ae', 'oe', 'aa', 'Ae', 'Oe', 'Aa'],
                ],
                'de' => [
                    ['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü'],
                    ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
                ],
                'he' => [
                    ['א', 'ב', 'ג', 'ד', 'ה', 'ו'],
                    ['ז', 'ח', 'ט', 'י', 'כ', 'ל'],
                    ['מ', 'נ', 'ס', 'ע', 'פ', 'צ'],
                    ['ק', 'ר', 'ש', 'ת', 'ן', 'ץ', 'ך', 'ם', 'ף'],
                ],
                'ro' => [
                    ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
                    ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
                ],
            ];
        }

        return $languageSpecific[$language] ?? null;
    }

    /**
     * 人性化方式显示单位化的数据大小字符串
     *
     * @param int $size
     * @param int $precision
     * @param string $separator
     * @return string
     */
    public static function humanDataSize($size, $precision = 2, $separator = '')
    {
        if ($size <= 0) {
            return '0';
        }

        $units = [
            'B', // Byte = 8 bit
            'KB', // Kilo Byte
            'MB', // Mega Byte
            'GB', // Giga Byte
            'TB', // Tera Byte
            'PB', // Peta Byte
            'EB', // Exa Byte
            'ZB', // Zetta Byte
            'YB', // Yotta Byte
            'BB', // Bronto Byte
            'NB', // Nona Byte，http://baike.baidu.com/view/3353.htm#6
            'DB', // Dogga Byte，http://baike.baidu.com/view/53484.htm
            'CB', // Corydon Byte，http://baike.baidu.com/view/14958.htm
        ];
        return round($size / pow(1024, $i = floor(log($size, 1024))), $precision)
                . $separator
                . $units[$i];
    }

    /**
     * 人性化方式显示单位化的数量字符串
     * 
     * @param int $num
     * @param int $precision
     * @param array $units
     * @param string $separator
     * @return string
     */
    public static function humanQuantity($num, $precision = 2, $units = ['e', 'w'], $separator = '')
    {
        if ($num >= ($quantity = 100000000) && isset($units[0])) {
            $unit = $units[0];
        } elseif ($num >= ($quantity = 10000) && isset($units[1])) {
            $unit = $units[1];
        // } elseif ($num >= ($quantity = 1000) && isset($units[2])) {
        //     $unit = $units[2];
        } else {
            $unit = '';
            $quantity = 1;
            $precision = 0;
        }
        $result = $num == $quantity ? 1 : number_format($num / $quantity, $precision, '.', '');
        return floatval($result) . $separator . $unit;
    }

	/**
	 * 转换字符串编码
	 *
	 * @param string $string
	 * @param string $destination
	 * @param mixed $source 默认为内部编码
	 * @return string
	 */
    public static function convert($string, $destination = 'utf-8', $source = null)
	{
		if (empty($source)) {
            $source = mb_internal_encoding();
        }
        return mb_convert_encoding($string, $destination, $source);
	}

    /**
     * GBK编码字符串转成UTF8编码字符串
     * 
     * @param string $string
     * @return string
     */
    public static function gbkToUtf8($string)
    {
        return iconv('GBK', 'UTF-8//IGNORE', $string);
    }

    /**
     * UTF8编码字符串转成GBK编码字符串
     * 
     * @param string $str
     * @return string
     */
    public static function utf8ToGbk($str)
    {
        $string = '';
        if ($str < 0x80) {
            $string .= $str;
        } elseif ($str < 0x800) {
            $string .= chr(0xC0 | $str >> 6);
            $string .= chr(0x80 | $str & 0x3F);
        } elseif ($str < 0x10000) {
            $string .= chr(0xE0 | $str >> 12);
            $string .= chr(0x80 | $str >> 6 & 0x3F);
            $string .= chr(0x80 | $str & 0x3F);
        } elseif ($str < 0x200000) {
            $string .= chr(0xF0 | $str >> 18);
            $string .= chr(0x80 | $str >> 12 & 0x3F);
            $string .= chr(0x80 | $str >> 6 & 0x3F);
            $string .= chr(0x80 | $str & 0x3F);
        }
        return iconv('UTF-8', 'GBK//IGNORE', $string);
    }

    /**
     * 清理字符串中的标点符号
     * 
     * @param string $string
     * @return string
     */
    public static function removePunctuation($string)
    {
        $pattern = "/[,.!;:<>\?'\"@#$%^&*\(\)\-_\+=\|\\\{\}\[\]\/`]/i";
        return preg_replace($pattern, '', self::semiangle($string));
    }

    /**
     * 全角字符串转为半角字符串
     * 
     * @param string $string
     * @return string
     */
    public static function semiangle($string)
    {
        return self::changeCase($string, self::STR_CASE_DBC);
    }

    /**
     * 使用反斜线"\"引用字符串
     * 
     * @param string $string
     * @return string
     */
    public static function addslashes($string)
    {
        return addslashes($string);
    }

    /**
     * 反引用一个引用字符串（去除转义反斜线）
     * 
     * @param string $string
     * @return string
     */
    public static function stripslashes($string)
    {
        return stripslashes($string);
    }

    /**
     * 全角半角互相转换
     * 
     * @param string $string
     * @return string
     */
    public static function changeCase($string, $case = self::STR_CASE_DBC)
    {
        // 全角半角字符映射表
        $maps = [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7',
            '８' => '8', '９' => '9', 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F',
            'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N',
            'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T', 'Ｕ' => 'U', 'Ｖ' => 'V',
            'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l',
            'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't',
            'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z', '（' => '(', '）' => ')',
            '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '“' => '"', '”' => '"',
            '‘' => '\'', '’' => '\'', '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+',
            '—' => '-', '－' => '-', '～' => '-', '：' => ':', '。' => '.', '、' => ',', '，' => ',', '；' => ';',
            '？' => '?', '！' => '!', '…' => '-', '‖' => '|', '｜' => '|', '〃' => '"', '　' => ' ',
        ];

        if ($case == self::STR_CASE_SBC) {
            $maps = array_flip($maps);
        }

        return strtr($string, $maps);
    }

    /**
     * 简体汉字转成拼音
     * 
     * @param string $string
     * @param string $charset
     * @return string
     */
    public static function pinyin($string, $charset = 'utf-8')
    {
        $dataKeys = 'a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha'
                    . '|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|'
                    . 'cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er'
                    . '|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui'
                    . '|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang'
                    . '|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang'
                    . '|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue'
                    . '|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne'
                    . '|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen'
                    . '|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang'
                    . '|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|'
                    . 'she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|'
                    . 'tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu'
                    . '|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you'
                    . '|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|'
                    . 'zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo';

        $dataValues = '-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990'
                    . '|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725'
                    . '|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263'
                    . '|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003'
                    . '|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697'
                    . '|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211'
                    . '|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922'
                    . '|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468'
                    . '|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664'
                    . '|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407'
                    . '|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959'
                    . '|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652'
                    . '|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369'
                    . '|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128'
                    . '|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914'
                    . '|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645'
                    . '|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149'
                    . '|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087'
                    . '|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658'
                    . '|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340'
                    . '|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888'
                    . '|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585'
                    . '|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847'
                    . '|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055'
                    . '|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780'
                    . '|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274'
                    . '|-10270|-10262|-10260|-10256|-10254';

        $arr = array_combine(explode('|', $dataKeys), explode('|', $dataValues));
        arsort($arr);
        reset($arr);
        if (strtolower($charset) != 'gb2312') {
            $string = self::utf8ToGbk($string);
        }
        $data = '';
        for ($i = 0, $cnt = strlen($string); $i < $cnt; $i++) {
            $ord = ord(substr($string, $i, 1));
            if ($ord > 160) {
                $tmpOrd = ord(substr($string, ++$i, 1));
                $ord = $ord * 256 + $tmpOrd - 65536;
            }
            $data .= self::pinyinly($ord, $arr);
        }
        return preg_replace("/[^a-z0-9]*/", '', $data);
    }

    /**
     * 生成拼音字符串
     * 
     * @param int $num
     * @param array $data
     * @return string
     */
    protected static function pinyinly($num, $data)
    {
        if ($num > 0 && $num < 160) {
            return chr($num);
        } elseif ($num < -20319 || $num > -10247) {
            return '';
        } else {
            $key = '';
            foreach ($data as $key => $value) {
                if ($value <= $num) {
                    break;
                }
            }
            return $key;
        }
    }
}
