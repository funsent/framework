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

namespace funsent\support\geo;

/**
 * 地理信息服务
 */
class Geo
{
    /**
     * 地球半径，单位米
     */
    const EARTH_POL_RADIUS = 6356908; // 极半径，地心到北极或南极的距离（两极的差极小，可以忽略） 
    const EARTH_EQU_RADIUS = 6377830; // 赤道半径，地心到赤道的距离
    const EARTH_AVG_RADIUS = 6371393; // 平均半径，地心到地球表面所有各点距离的平均值

    /**
     * 根据经纬度计算两点之间的直线距离，单位千米km
     *
     * @param float $lon1
     * @param float $lat1
     * @param float $lon2
     * @param float $lat2
     * @param int $unit 单位 1:米 2:千米
     * @param int $precision 精度
     * @return float
     */
    public static function resolveDistanceByLonAndLat($lon1, $lat1, $lon2, $lat2, $unit = 2, $precision = 2)
    {
        $lon1 = ($lon1 * M_PI) / 180;
        $lat1 = ($lat1 * M_PI) / 180;
        $lon2 = ($lon2 * M_PI) / 180;
        $lat2 = ($lat2 * M_PI) / 180;

        $longInterval = $lon2 - $lon1;
        $latInterval = $lat2 - $lat1;

        $distance = pow(sin($latInterval / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($longInterval / 2), 2);
        $distance = self::EARTH_AVG_RADIUS * (2 * asin(min(1, sqrt($distance))));
        if ($unit == 2) {
            $distance /= 1000;
        }

        return abs(round($distance, $precision));
    }

    /**
     * 根据百度GPS经纬度信息解析并返回地区数据
     * 
     * http://lbsyun.baidu.com/index.php?title=webapi
     *
     * @param float $lon
     * @param float $lat
     * @param string $key
     * @return array|false
     */
    public static function resolveRegion($lon, $lat, $key = '')
    {
        if (empty($key)) {
            return false;
        }
        if ($lat > 0 && $lon > 0) {
            $url = sprintf('http://api.map.baidu.com/reverse_geocoding/v3/?ak=%s&output=json&coordtype=wgs84ll&location=%s,%s', $key, $lat, $lon);
            if ($curlRes = file_get_contents($url)) {
                $res = json_decode($curlRes, true);
                if ($res['status'] == 0) {
                    $data['province'] = $res['result']['addressComponent']['province'];
                    $data['city']     = $res['result']['addressComponent']['city'];
                    $data['district'] = $res['result']['addressComponent']['district'];
                    $data['address']  = $res['result']['formatted_address'];
                    return json_encode($data, JSON_UNESCAPED_UNICODE);
                }
            }
        }
        return false;
    }
}
