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
 * 费用金额工具类
 */
class Money
{
    /**
     * 将数值金额转换为中文大写金额
     *
     * @param float|int|string $amount
     * @return mixed
     */
    public static function cny($amount)
    {
        if (!is_numeric($amount) || $amount < 0) {
            return false;
        }

        if ($amount == 0) {
            return '零元整';
        }

        // 金额不能超过12位
        if (strlen($amount) > 12) {
            return false;
        }

        $result    = '';
        $amountArr = explode('.', $amount);
        $digital  = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
        $position = ['仟', '佰', '拾', '亿', '仟', '佰', '拾', '万', '仟', '佰', '拾', '元'];

        // 整数部分转换
        $integerArr       = str_split($amountArr[0], 1);
        $integerArrLength = count($integerArr);
        $positionLength   = count($position);
        $zeroCount = 0;
        for ($i = 0; $i < $integerArrLength; $i++) {
            if ($integerArr[$i] != 0) {
                if ($zeroCount >= 1) {
                    $result .= $digital[0];
                }
                $result .= $digital[$integerArr[$i]] . $position[$positionLength - $integerArrLength + $i];
                $zeroCount = 0;
            } else {
                $zeroCount += 1;
                // 如果数值为0, 且单位是亿,万,元这三个的时候,则直接显示单位
                if (($positionLength - $integerArrLength + $i + 1) % 4 == 0) {
                    $result = $result . $position[$positionLength - $integerArrLength + $i];
                }
            }
        }

        // 小数部分转换
        if (isset($amountArr[1])) {
            $decimalArr = str_split($amountArr[1], 1);
            if ($decimalArr[0] != 0) {
                $result = $result . $digital[$decimalArr[0]] . '角';
            }
            if ($decimalArr[1] != 0) {
                $result = $result . $digital[$decimalArr[1]] . '分';
            }
        } else {
            $result = $result . '整';
        }

        return $result;
    }
}
