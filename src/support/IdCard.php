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
 * 身份证号工具类
 * 遵守中华人民共和国GB11643一1999标准规定的第二代身份证号制定标准
 * 
 * @method  bool check(string $id, bool $parseArea = false)
 * @method  array parse(string $id, bool $parseArea = false)
 * @package funsent
 * @author  yanggf <2018708@qq.com>
 */
class IdCard
{
    public static $data = [];

    /**
     * 解析身份证号，检查合法性
     * 
     * @param string $cardId 身份证号
     * @param bool $parseArea 解析地区
     * @return array
     */
    public static function parse($cardId, $parseArea = false)
    {
        if (!self::check($cardId, $parseArea)) {
            return [];
        }
        return self::$data;
    }

    /**
     * 检查身份证号合法性
     * 该方法可以尝试检查17位或19位的错误号码，尝试获取可能合法的18位身份证号
     * 
     * @param string $cardId 身份证号
     * @param bool $parseArea 解析地区
     * @return bool
     */
    public static function check($cardId, $parseArea = false)
    {
        self::$data = [
            'last_bit' => null, // 18位身份证号最后一位校验位
            'like_id'  => null, // 修正后可能合法的身份证号

            // 如果身份证号解析正确，则一下参数会被填充
            'id15'     => null, // 15位身份证号
            'id18'     => null, // 18位身份证号
            'gender'   => null, // 性别
            'birth'    => null, // 出生日期
            'age'      => null, // 年龄（周岁）

            'area'     => null, // 地区
        ];

        $length = strlen($cardId);

        if ($length == 17) {
            $lastBit = self::getLastBit($cardId);
            self::$data['last_bit'] = $lastBit;
            self::$data['like_id'] = $cardId . $lastBit; // 修正后可能合法的身份证号
            return false;
        }

        if ($length == 19) {
            $lastBit = self::getLastBit($cardId);
            self::$data['last_bit'] = $lastBit;
            self::$data['like_id'] = substr($cardId, 0, 17) . $lastBit; // 修正后可能合法的身份证号
            return false;
        }

        if ($length == 18) {
            $lastBit = self::getLastBit($cardId);
            if ($lastBit == $cardId[17]) {
                $data = [
                    'last_bit' => $lastBit,
                    'like_id'  => $cardId,
                    'id15'     => self::to15($cardId),
                    'id18'     => $cardId,
                    'gender'   => self::gender($cardId),
                    'birth'    => self::birth($cardId),
                    'age'      => self::age($cardId),
                ];

                if ($parseArea) {
                    $data['area'] = self::area($cardId);
                }

                self::$data = array_merge(self::$data, $data);
                return true;
            }
            self::$data['like_id'] = substr($cardId, 0, 17) . $lastBit; // 修正后可能合法的身份证号
            return false;
        }

        if ($length == 15) {

            // 检查出生日期
            $birth = self::birth($cardId);
            $birthday = date_create($birth);
            if (!$birthday || ($birth != $birthday->format('Y-m-d'))) {
                return false;
            }

            // 检查行政区划
            if ($parseArea && $area = self::area()) {
                $code = substr($cardId, 0, 6);
                if (!isset($area[$code])) {
                    return false;
                }
                $tmpArea = $area[$code];
            }
            
            $data = [
                'id15'     => $cardId,
                'id18'     => self::to18($cardId),
                'gender'   => self::gender($cardId),
                'birth'    => $birth,
                'age'      => self::age($cardId),
            ];

            if (isset($tmpArea)) {
                $data['area'] = $tmpArea;
            }

            self::$data = array_merge(self::$data, $data);
            return true;
        }

        return false;
    }

    /**
     * 15位转18位(第一代身份证转第二代身份证)，不检查合法性
     * 
     * @param string $cardId15 15位身份证号
     * @return string
     */
    protected static function to18($cardId15)
    {
        if (strlen($cardId15) != 15) {
            return $cardId15;
        }

        $cardId18 = substr($cardId15, 0, 6) . '19' . substr($cardId15, 6);
        $cardId18 .= self::getLastBit($cardId18);
        return $cardId18;
    }

    /**
     * 18位转15位(第二代身份证转第一代身份证)，不检查合法性
     * 
     * @param string $cardId18 18位身份证号
     * @return string
     */
    protected static function to15($cardId18)
    {
        if (strlen($cardId18) != 18) {
            return $cardId18;
        }

        // // 2000年及以后出生的不做转换
        // if (substr($cardId18, 6, 2) != 19) {
        //     return $cardId18;
        // }

        $cardId15 = substr($cardId18, 0, 6) . substr($cardId18, 8, 9);
        return $cardId15;
    }

    /**
     * 获取性别，不检查合法性
     * 
     * @param string $cardId 身份证号
     * @return int 2男 1女 0未知
     */
    protected static function gender($cardId)
    {
        $length = strlen($cardId);
        if ($length == 15) {
            $genderBit = $cardId[14];
            if (!in_array($genderBit, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9])) {
                return 0;
            }
            return (intval($genderBit) % 2 === 0) ? 1 : 2;
        } elseif ($length == 18) {
            $genderBit = $cardId[16];
            if (!in_array($genderBit, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9])) {
                return 0;
            }
            return (intval($genderBit) % 2 === 0) ? 1 : 2;
        } else {
            return 0;
        }
    }

    /**
     * 获取出生日期，不检查合法性
     * 
     * @param string $cardId 身份证号
     * @return string
     */
    protected static function birth($cardId)
    {
        $length = strlen($cardId);
        if ($length == 15) {
            return sprintf('19%s-%s-%s', substr($cardId, 6, 2), substr($cardId, 8, 2), substr($cardId, 10, 2));
        } elseif ($length == 18) {
            return sprintf('%s-%s-%s', substr($cardId, 6, 4), substr($cardId, 10, 2), substr($cardId, 12, 2));
        } else {
            return '';
        }
    }

    /**
     * 获取周岁的年龄，不检查合法性
     * 
     * @param string $cardId 身份证号
     * @return int
     */
    protected static function age($cardId)
    {
        $birth = self::birth($cardId);
        if ($birth == '') {
            return 0;
        }
        $interval = date_diff(date_create($birth), date_create());
        return (int) $interval->format('%y');
    }

    /**
     * 获取行政区划
     * 
     * @param string|null $cardId
     * @return array
     */
    protected static function area($cardId = null)
    {
        static $area = [];
        if (empty($area)) {
            $area = include __DIR__ . '/idcard/address.php';
        }
        if (!is_null($cardId)) {
            if (!in_array(strlen($cardId), [15, 18])) {
                return '';
            }
            $province   = substr($cardId, 0, 2);
            $city       = substr($cardId, 2, 2);
            $district   = substr($cardId, 4, 2);
            $zone       = '';
            if (isset($area[$province . '0000'])) {
                $zone .= $area[$province . '0000'];
            }
            if (isset($area[$province . $city . '00'])) {
                $zone .= $area[$province . $city . '00'];
            }
            if (isset($area[$province . $city . $district])) {
                $zone .= $area[$province . $city . $district];
            }
            return $zone;
        }
        return $area;
    }

    /**
     * 获取校验位（第二代身份证最后一位）
     * 
     * @param string $cardId 身份证号
     * @return string
     */
    protected static function getLastBit($cardId)
    {
        // 加权因子
        $factors = [
            18 => 7, 17 => 9, 16 => 10, 15 => 5, 14 => 8,
            13 => 4, 12 => 2, 11 => 1, 10 => 6, 9 => 3, 8 => 7,
            7 => 9, 6 => 10, 5 => 5, 4 => 8, 3 => 4, 2 => 2, 1 => 1
        ];
        // 校验码字符值对照表
        $codes = [
            0 => '1', 1 => '0', 2 => 'X', 3 => '9',
            4 => '8', 5 => '7', 6 => '6', 7 => '5',
            8 => '4', 9 => '3', 10 => '2'
        ];
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += intval($cardId[$i]) * $factors[18 - $i];
        }
        $mod = $total % 11;
        return $codes[$mod];
    }
}
