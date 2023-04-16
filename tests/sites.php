<?php

//ob_start();
set_time_limit(0);

$uri = 'https://www.114la.com';
$headers = get_headers($uri, 1);
if (false === strpos($headers[0], '200')) {
    die(sprintf("'<b>%s</b>' is bad uri.", $uri));
}

$html = file_get_contents($uri);

$dom = new DOMDocument();
@$dom->loadHTML($html);

// grab all the on the page
$xpath = new DOMXPath($dom);
$hrefs = $xpath->evaluate("/html/body//a");

// 抓取URL
ob_clean();
$sites = [];
for ($i = 0, $cnt = $hrefs->length; $i < $cnt; $i++) {
    $href = $hrefs->item($i);
    $url = $href->getAttribute('href');
    $url = get_valid_url($url);
    if (!$url) {
        continue;
    }

    if (!in_array($url, $sites)) {
        echo str_repeat(' ', 1024 * 1024 * 4);
        echo sprintf('<a href="%s" target="_blank">%s</a><br />', $url, $url);
        $sites[] = $url;
        ob_flush();
        flush();
    }
}

function get_valid_url($url)
{
    if (!$res = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        return false;
    }

    if (!$arr = parse_url($res)) {
        return false;
    }

    $scheme = (isset($arr['scheme']) && $arr['scheme'] != 'http') ? $arr['scheme'] : 'http';
    $port = (isset($arr['port']) && $arr['port'] != 80) ? $arr['port'] : '';
    $url = $scheme . '://' . $arr['host'] . $port;

    $headers = @get_headers($url, 1);
    if (false === $headers) {
        return false;
    }

    $statusArr = explode(' ', $headers[0]);
    if ($statusArr[1] == '200' || $statusArr[1] == '302') {
        return $url;
    }
    if ($statusArr[1] == '301') {
        return get_valid_url($headers['Location']);
    }

    return false;
}

//$sites = array_unique($sites); // 去重
//$sites = array_values($sites); // 重建索引
//var_dump($sites);

//ob_end_flush();
