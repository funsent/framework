<?php

namespace funsent;

use DOMDocument;
use DOMXPath;

$spider = new UrlSpider('http://www.10jqka.com.cn/');
$spider->run();

class UrlSpider
{
    protected $headers = [];
    protected $html = '';
    protected $sites = [];

    public function __construct($uri)
    {
        set_time_limit(0);
        if (!$headers = $this->getHeaders($uri)) {
            die(sprintf("'<b>%s</b>' is bad uri，error：%s", $uri));
        }

        if (!$html = $this->getHtmlStr($uri)) {
            die(sprintf("read failure for %s", $uri));
        }

        $this->headers = $headers;
        $this->html = $html;
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function run($str = '')
    {
        $html = $str ?: $this->getHtml();

        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $hrefs = $xpath->evaluate("/html/body//a");

        // 抓取URL
        ob_clean();
        $this->sites = [];
        for ($i = 0, $cnt = $hrefs->length; $i < $cnt; $i++) {

            $url = $hrefs->item($i)->getAttribute('href');
            $site = $this->getValidSite($url);
            if (!$site) {
                continue;
            }

            if (in_array($site, $this->sites)) {
                continue;
            }

            $this->sites[] = $site;
            echo str_repeat(' ', 1024 * 1024 * 4); // 保证足够的输出量
            echo sprintf('<a href="%s" target="_blank">%s</a><br />', $site, $site);
            ob_flush();
            flush();
        }
    }

    protected function getValidSite($url)
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

        if (!$headers = $this->getHeaders($url)) {
            return false;
        }
        $statusArr = explode(' ', $headers[0]);
        if (in_array($statusArr[1], ['200', '302'])) {
            return $url;
        } elseif ($statusArr[1] == '301') {
            return $this->getValidSite($headers['Location']);
        }

        return false;
    }

    protected function getHtmlStr($uri)
    {
        if (!$str = file_get_contents($uri)) {
            return false;
        }
        return $str;
    }

    protected function getHeaders($uri)
    {
        $headers = @get_headers($uri, 1);
        if (false === $headers) {
            return false;
        }

        $statusArr = explode(' ', $headers[0]);
        if (!in_array($statusArr[1], ['200', '301', '302'])) {
            return false;
        }

        return $headers;
    }
}