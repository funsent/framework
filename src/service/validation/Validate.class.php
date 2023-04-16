<?php

/*
	(C)2016 funsent.com Inc. All Rights Reserved.
	$Id$
*/

/**
 * 验证类
 */
namespace Libs\Funsent;
class Validate
{
	// 验证中国大陆身份证号
	static public function isIdCardNumber($idCardNumber)
	{
		$idCard = new \Libs\Funsent\IdCardNumber;
		return $idCard->verify($idCardNumber);
	}

	// 验证中国大陆邮政编码
	static public function isPostcode($postcode)
	{
		$regex = '/^\d{6}$/';
		return preg_match($regex, $postcode) ? true : false;
	}

	// 验证中国大陆手机号码
	static public function isMobile($mobile)
	{
		$regex = '/^(13[0-9]|14[5|7]|15[0-9]|17[0-9]|18[0-9])\d{8}$/u';
		return preg_match($regex, $mobile) ? true : false;
	}

	// 验证中国大陆座机号码和固话号码
	static public function isPhone($phone)
	{
		$regex = '/\d{3,4}-\d{6,8}/';
		return preg_match($regex, $phone) ? true : false;
	}
	static public function isTel($tel)
	{
		return self::isPhone($tel);
	}

	// 验证名称（包括人名、学校名、地名、建筑名等）
	static public function isName($name, $min = 2, $max = 24)
	{
		$name = str_replace(' ', '', $name);
		$regex = '/^[\x{4e00}-\x{9fa5}A-Za-z0-9]{' . $min . ',' . $max . '}$/u';
		return preg_match($regex, $name) ? true : false;
	}

	// 验证地址
	static public function isAddress($address, $min = 5, $max = 50)
	{
		// 不能输入的字符：' " < > 换行符
		$filters = array(
			'`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '+', '=', '_',
			'[', ']', '{', '}', '|', ';', ':', ',', '.', '/', '?', '·', '～', '！', ',',
			'＠', '＃', '￥', '％', '…', '＆', '（', '）', '×', '－', '＋', '＝', '——', '【', '】',
			'、', '{', '}', '｛', '｝', '《', '》', '｜', '；', '‘', '＇', '：', '“', '＂', '，', '。',
			'．', '／', '＼', '＜', '＞', '？', '　', ' '
		);
		$address = str_replace($filters, '', $address);
		return self::isName($address, $min, $max);
	}

	// 验证备注
	static public function isRemark($remark, $min = 5, $max = 80)
	{
		// 不能输入的字符：' " < >
		$filters = array(
			'`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '+', '=', '_',
			'[', ']', '{', '}', '|', ';', ':', ',', '.', '/', '?', '·', '～', '！', ',',
			'＠', '＃', '￥', '％', '…', '＆', '（', '）', '×', '－', '＋', '＝', '——', '【', '】',
			'、', '{', '}', '｛', '｝', '《', '》', '｜', '；', '‘', '＇', '：', '“', '＂', '，', '。',
			'．', '／', '＼', '＜', '＞', '？', '　', ' ', "\\", "\r\n", "\r", "\n"
		);
		$remark = str_replace($filters, '', $remark);
		return self::isName($remark, $min, $max);
	}

	// 验证用户名，只能输入中文、英文字母、0-9数字
	static public function isUsername($username, $min = 2, $max = 24)
	{
		$regex = '/^[\x{4e00}-\x{9fa5}A-Za-z0-9_-]{' . $min . ',' . $max . '}$/u';
		return preg_match($regex, $username) ? true : false;
	}

	// 验证密码，只能输入英文字符和0-9数字
	static public function isPassword($password, $min = 4, $max = 24)
	{
		// 不能输入的字符：中文和全角字符
		$filters = array(
			'`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '+', '=', '_',
			'[', ']', '{', '}', '|', ';', ':', ',', '.', '/', '?', '·'
		);
		$password = str_replace($filters, '', $password);
		$regex = '/^[a-zA-Z0-9]{' . $min . ',' . $max . '}$/i';
		return preg_match($regex, $password) ? true : false;
	}

	// 验证是否为中文汉字
	static public function isChinese($chinese)
	{
		$regex = '/^[\x{4e00}-\x{9fa5}]+$/i';
		return preg_match($regex, $chinese) ? true : false;
	}

	// 验证是否位英文字母
	static public function isEnglish($english)
	{
		$regex = '/^[A-Za-z]+$/';
		return preg_match($regex, $english) ? true : false;
	}

	// 验证电子邮箱
	static public function isEmail($email)
	{
		$regex = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i';
		return preg_match($regex, $email) ? true : false;
	}

	// 验证路径
	static public function isPath($path)
	{
		$regex = '/^([A-Za-z]+\/)+[A-Za-z]{1,}$/i';
		return preg_match($regex, $path) ? true : false;
	}

	// 验证URL
	static public function isUrl($url)
	{
		$regex = '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/i';
		return preg_match($regex, $url) ? true : false;
	}

	// 验证数字
	static public function isNumber($number, $min = 0, $max = '')
	{
		$regex = '/^\d{' . $min . ',' . $max . '}$/';
		return preg_match($regex, $number) ? true : false;
	}

	// 验证IP地址
	static public function isIP($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP) ? true : false;
	}

	// 验证IPv4地址
	static public function isIPv4($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? true : false;
	}

	// 验证是否是合法的公共IPv4地址，192.168.1.1这类的私有IP地址将会排除在外
	static public function isIPv4NoPriv($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE) ? true : false;
	}

	// 验证IPv6地址
	static public function isIPv6($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) ? true : false;
	}

	// 验证是否是合法的公共IPv4地址或者是合法的公共IPv6地址
	static public function isIPv4NoPrivOrIPv6($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? true : false;
	}
}
