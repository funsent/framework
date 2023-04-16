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
 * PHP环境探针
 * 
 * @author yanggf <2018708@qq.com>
 * @package funsent
 */
class Environment
{
    /**
     * 判断是否是类Unix系统
     *
     * @return bool
     */
    public static function isLikeUnix()
    {
        return '/' == DIRECTORY_SEPARATOR;
    }

    /**
     * 后去服务器IP
     *
     * @return string
     */
    public static function getServerIp()
    {
        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }
        return \gethostbyname(\php_uname('n'));
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    public static function getRemoteIp()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return \preg_replace('/^.+,\s*/', '', $_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }

    /**
     * 获取客户端代理字符串
     *
     * @return string
     */
    public static function getRemoteAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * 格式化字节单位
     *
     * @param int $bytes
     * @return string
     */
    public static function humanFilesize($bytes)
    {
        if ($bytes == 0)
            return '0 B';

        $units = ['B', 'K', 'M', 'G', 'T'];
        $size = '';
        while ($bytes > 0 && \count($units) > 0) {
            $size = \strval($bytes % 1024) . ' ' . \array_shift($units) . ' ' . $size;
            $bytes = \intval($bytes / 1024);
        }
        return $size;
    }

    /**
     * 获取时间、时区信息
     *
     * @return array
     */
    public static function getTimeInfo()
    {
        return [
            'timezone' => 'GMT' . \date('P'),
            'time'     => \date('Y-m-d H:i:s T (e)'),
            'utc_time' => \gmdate('Y-m-d H:i:s T (e)'),
        ];
    }

    /**
     * 获取系统信息
     *
     * @return array
     */
    public static function getSystemInfo($dbconn = '')
    {
        $os = \explode(' ', \php_uname());
        $osLocale = \setlocale(LC_CTYPE, 0);
        if ($osLocale == 'C') {
            $osLocale = 'POSIX';
        }

        $databaseInfo = self::getDatabaseInfo($dbconn);

        $webServer = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $phpEngine = 'PHP' . \phpversion() . ', ' . \php_sapi_name();
        $zendEngine = \function_exists('\\zend_version') ? 'Zend Engine v' . \zend_version() : '';
        $engine = $phpEngine . ', ' . $zendEngine;
        $webServer .= $webServer ? ' (' . $engine . ')' : $engine;

        $webDomain = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'];
        $webDomain = $webDomain . ':' . ($_SERVER['SERVER_PORT'] ?? '80');

        $info = [
            'Host'              => self::isLikeUnix() ? $os[1] : $os[2],
            'OS'                => self::getOsReleaseName(),
            'OS Flag'           => \php_uname(),
            'OS Locale'         => $osLocale,
            'Server IP'         => self::getServerIp(),
            'DB Server'         => $databaseInfo['server'],
            'DB Client'         => $databaseInfo['client'],
            'Web Server'        => $webServer,
            'Web User'          => \get_current_user(),
            'Web Domain'        => $webDomain,
            'Web Root'          => $_SERVER['DOCUMENT_ROOT'] ?? \str_replace('\\', '/', __DIR__),
            'Web Scirpt'        => \str_replace('\\', '/', __FILE__) ?? $_SERVER['SCRIPT_FILENAME'],
            'Web Administrator' => $_SERVER['SERVER_ADMIN'] ?? '',
            'Client IP'         => self::getRemoteIp(),
            'Client UA'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'Client Language'   => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        ];
        return $info;
    }

    /**
     * 获取数据库相关信息
     *
     * @param object $conn
     * @return array
     */
    public static function getDatabaseInfo($conn)
    {
        $info = [
            'driver' => '',
            'server' => '',
            'client' => '',
        ];
        if ($conn instanceof \PDO) {
            $dbDriverName    = $conn->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $dbServerVersion = $conn->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $dbClientVersion = $conn->getAttribute(\PDO::ATTR_CLIENT_VERSION);
            switch (strtolower($dbDriverName)) {
                case 'mysql':
                    $query = $conn->prepare('SELECT @@version as `version`, @@version_comment as `name`');
                    $query->execute();
                    while ($row = $query->fetch(\PDO::FETCH_OBJ)) {
                        $info['server'] = $row->name . ' v' . $row->version;
                    }
                    break;
                default:
                    $info['driver'] = $dbDriverName;
                    $info['server'] = $dbServerVersion;
                    break;
            }
            $info['client'] = $dbClientVersion;
        }

        return $info;
    }

    /**
     * 获取实时统计信息
     *
     * @return array
     */
    public static function getStat()
    {
    }

    /**
     * 获取参数信息
     *
     * @return array
     */
    public static function getConfigInfo()
    {
        // opcache
        $opcache = false;
        $opcacheEnabled = \function_exists('\\opcache_get_configuration');
        if ($opcacheEnabled) {
            $opcacheConfiguration = \opcache_get_configuration();
            $opcache = $opcacheConfiguration['version']['opcache_product_name'] . ' v' . $opcacheConfiguration['version']['version'];
            if (isset($opcacheConfiguration['directives']['opcache.enable']) && true === $opcacheConfiguration['directives']['opcache.enable']) {
                $opcache .= ' [已启用]';
            }
        }

        // ionCube
        $ionCube = false;
        if (\extension_loaded('ioncube loader') && \function_exists('\\ioncube_loader_version')) {
            $ionCube = 'ionCube loader v' . (int) substr(\ioncube_loader_version(), 3, 2);
        }

        // sqlite
        $sqlite = false;
        if (\class_exists('\\SQLite3')) {
            $sqlite = 'SQLite3 v' . \SQLite3::version()['versionString'];
        } elseif (\function_exists('\\sqlite_libversion')) {
            $sqlite = 'SQLite v' . \sqlite_libversion();
        }

        // pdo
        $pdo = [];
        if (\extension_loaded('pdo_mysql')) {
            $pdo[] = 'MySQL';
        }
        if (\extension_loaded('pdo_pgsql')) {
            $pdo[] = 'PgSQL';
        }
        if (\extension_loaded('pdo_sqlite')) {
            $pdo[] = 'SQLite';
        }
        if (\extension_loaded('pdo_oci')) {
            $pdo[] = 'Oracle';
        }
        if (\extension_loaded('pdo_sqlsrv')) {
            $pdo[] = 'SQLServer';
        }
        if (\extension_loaded('pdo_ibm')) {
            $pdo[] = 'DB2';
        }

        $info = [
            'Zend Optimizer'         => \function_exists('\\zend_optimizer_version') ? 'Zend Optimizer v' .  \zend_optimizer_version() : false,
            'Zend GuardLoader'       => \extension_loaded('Zend Guard Loader') ? 'Zend Guard Loader' : false,
            'ionCubeLoader'          => $ionCube,
            'sourceGuardian'         => \extension_loaded('sourceguardian'),

            'Session'                => \function_exists('\\session_start'),
            'Cookie'                 => isset($_COOKIE),

            'OPCache'                => $opcache,
            'Xcache'                 => \phpversion('XCache'),
            'eAccelerator'           => \phpversion('eAccelerator'),
            'APC'                    => \phpversion('APC'),
            'WinCache'               => \function_exists('\\wincache_lock'),

            'Redis'                  => \extension_loaded('redis') && \class_exists('\\Redis'),
            'Memcache'               => \extension_loaded('memcache') && \class_exists('\\Memcache'),
            'Memcached'              => \extension_loaded('memcached') && \class_exists('\\Memcached'),

            'GD'                     => \function_exists('\\gd_info') ? \gd_info()['GD Version'] : false,
            'cURL'                   => \function_exists('\\curl_init'),
            'Mbstring'               => \extension_loaded('mbstring') && \function_exists('\\mb_substr'),
            'Multibyte String'       => \function_exists('\\mb_detect_encoding'),
            'Gzip'                   => \function_exists('\\gzclose'),
            'Zip'                    => \extension_loaded('zip') && \class_exists('\\ZipArchive'),
            'Soap'                   => \class_exists('\\SoapClient'),
            'Socket'                 => \extension_loaded('sockets') && \function_exists('\\socket_accept'),
            'Swoole'                 => \extension_loaded('swoole') && \function_exists('\\swoole_version'),

            'Xdebug'                 => \extension_loaded('xdebug'),

            'Openssl'                => \function_exists('\\openssl_encrypt'),
            'Sodium'                 => \function_exists('\\sodium_add'),
            'Hash'                   => \function_exists('\\hash_init'),
            'Mhash'                  => \function_exists('\\mhash_count'),
            'Mcrypt'                 => \function_exists('\\mcrypt_encrypt'),

            'SimpleXML'              => \extension_loaded('simplexml'),
            'XML'                    => \function_exists('\\xml_set_object'),
            'SMTP'                   => \get_cfg_var('SMTP'),
            'FTP'                    => \function_exists('\\ftp_login'),
            'Calendar'               => \extension_loaded('calendar'),
            'ImageMagick'            => \extension_loaded('imagick') && \class_exists('\\Imagick'),
            'Gmagick'                => \extension_loaded('gmagick') && \class_exists('\\Gmagick'),
            'Exif'                   => \extension_loaded('exif') && \function_exists('\\exif_imagetype'),
            'Fileinfo'               => \extension_loaded('fileinfo'),
            'Phalcon'                => \extension_loaded('phalcon'),
            'IMAP'                   => \function_exists('\\imap_create'),
            'JDToGregorian'          => \function_exists('\\JDToGregorian'),
            'WDDX'                   => \function_exists('\\wddx_add_vars'),
            'Iconv'                  => \function_exists('\\iconv'),
            'LDAP'                   => \function_exists('\\ldap_connect'),
            'GUI'                    => \class_exists('\\UI\\Window'),

            'Safe mode'              => (bool) \get_cfg_var('safe_mode'),
            'Display errors'         => (bool) \get_cfg_var('display_errors'),
            'Memory limit'           => \get_cfg_var('memory_limit'),
            'Post max size'          => \get_cfg_var('post_max_size'),
            'Upload max filesize'    => \get_cfg_var('upload_max_filesize'),
            'Max execution time'     => \get_cfg_var('max_execution_time') . 's',
            'Default socket timeout' => \get_cfg_var('default_socket_timeout') . 's',
            'Precision'              => \get_cfg_var('precision'),
            'BCMath'                 => \function_exists('\\bcadd'),
            'Magic quotes gpc'       => (bool) \get_cfg_var('magic_quotes_gpc'),
            'Magic quotes runtime'   => (bool) \get_cfg_var('magic_quotes_runtime'),
            'Register argc argv'     => (bool) \get_cfg_var('register_argc_argv'),
            'Allow url fopen'        => (bool) \get_cfg_var('allow_url_fopen'),
            'Register globals'       => (bool) \get_cfg_var('register_globals'),
            'Short open tag'         => (bool) \get_cfg_var('short_open_tag'),
            'Asp tags'               => (bool) \get_cfg_var('asp_tags'),
            'Doc root'               => \get_cfg_var('doc_root'),
            'User dir'               => \get_cfg_var('user_dir'),
            'Include path'           => \get_cfg_var('include_path'),
            'Enable dl'              => (bool) \get_cfg_var('enable_dl'),
            'Ignore repeated errors' => (bool) \get_cfg_var('ignore_repeated_errors'),
            'Ignore repeated source' => (bool) \get_cfg_var('ignore_repeated_source'),
            'Report memleaks'        => (bool) \get_cfg_var('report_memleaks'),
            'Pspell check'           => \function_exists('\\pspell_check'),
            'Bcadd'                  => \function_exists('\\bcadd'),
            'Preg Match'             => \function_exists('\\preg_match'),
            'PDF'                    => \function_exists('\\pdf_close'),
            'SNMP'                   => \function_exists('\\snmpget'),
            'VM'                     => \function_exists('\\vm_adduser'),

            'PDO'                    => $pdo ? implode(' ', $pdo) : false,
            'ODBC'                   => \function_exists('\\odbc_close'),
            'DBA'                    => \function_exists('\\dba_close'),
            'dbx'                    => \function_exists('\\dbx_connect'),

            'MySQL'                  => \function_exists('\\mysqli_connect') ? 'mysqli' : (\function_exists('\\mysql_connect') ? 'mysql' : false),
            'PgSQL'                  => \function_exists('\\pg_connect'),
            'SQLite'                 => $sqlite,
            'Oracle'                 => \function_exists('\\oci_connect'),
            'SQLServer'              => \function_exists('\\mssql_connect') || \function_exists('\\sqlsrv_connect'),
            'Sybase'                 => \function_exists('\\sybase_connect'),
            'DB2'                    => \function_exists('\\db2_connect'),
            'MongoDB'                => \class_exists('\\MongoDB') && \class_exists('\\MongoDB\\Driver\\Manager'),
            'dBase'                  => \function_exists('\\dbase_create'),
            'mSQL'                   => \function_exists('\\msql_connect'),
            'MaxDB'                  => \function_exists('\\maxdb_connect'),
            'Informix'               => \function_exists('\\ifx_connect'),
            'filePro'                => \function_exists('\\filepro_fieldcount'),
        ];

        return $info;
    }

    /**
     * 获取已加载模块信息
     *
     * @return array
     */
    public static function getLoadedExtensions()
    {
        return \get_loaded_extensions();
    }

    /**
     * 获取模块的函数
     *
     * @param string $extension
     * @return array
     */
    public static function getFunctionsByExtension($extension)
    {
        return \get_extension_funcs(\strtolower($extension));
    }

    /**
     * 获取已定义的函数
     *
     * @param string $type
     * @return array
     */
    public static function getDefinedFunctions($type = 'internal')
    {
        if (!\in_array($type, ['internal', 'user'])) {
            return [];
        }
        return \get_defined_functions()[$type];
    }

    /**
     * 获取已禁用的函数
     *
     * @return string
     */
    public static function getDisabledFunctions()
    {
        return \get_cfg_var('disable_functions');
    }


    /**
     * 读取信息信息
     *
     * @param string $file
     * @param int $flags
     * @param bool $falseForWindows
     * @return mixed
     */
    protected static function readSysInfo($file, $flags = 0, $falseForWindows = true)
    {
        if ($falseForWindows && !self::isLikeUnix()) {
            return false;
        }
        if (!@\is_readable($file)) {
            return false;
        }
        return \file($file, $flags);
    }

    /**
     * 获取系统进程的统计信息，CPU信息
     *
     * @return array
     */
    public static function getCpuUsage()
    {
        if (!$content = self::readSysInfo('/proc/stat')) {
            return [];
        }

        $arr = \preg_split('/\s+/', \trim(\array_shift($content)));
        return \array_slice($arr, 1);
    }

    /**
     * 获取Socket信息
     *
     * @return array
     */
    public static function getSocketInfo()
    {
        if (!$content = self::readSysInfo('/proc/net/sockstat')) {
            return [];
        }

        $info = [];
        foreach ($content as $line) {
            $parts        = \explode(':', $line);
            $key          = \trim($parts[0]);
            $values       = \preg_split('/\s+/', \trim($parts[1]));
            $info[$key]   = [];
            for ($i = 0; $i < \count($values); $i += 2) {
                $info[$key][$values[$i]] = $values[$i + 1];
            }
        }
        return $info;
    }

    /**
     * 获取CPU信息
     *
     * @return array
     */
    public static function getCpuInfo()
    {
        if (!$content = self::readSysInfo('/proc/cpuinfo')) {
            return [];
        }

        $str = \implode('', $content);
        \preg_match_all("/processor\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $processor);
        \preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
        if (count($model[0]) == 0) {
            \preg_match_all("/Hardware\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
        }
        \preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $mhz);
        if (count($mhz[0]) == 0) {
            if ($values = self::readSysInfo('/sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_max_freq')) {
                $mhz = ['', [sprintf('%.3f', intval($values[0]) / 1000)]];
            }
        }
        \preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $str, $cache);
        \preg_match_all("/(?i)bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);
        \preg_match_all("/(?i)(flags|Features)\s{0,}\:+\s{0,}(.+)[\r\n]+/", $str, $flags);

        $info = [];
        if (\is_array($model[1])) {
            $info['num']          = \sizeof($processor[1]);
            $info['model']        = $model[1][0];
            $info['frequency']    = $mhz[1][0];
            $info['bogomips']     = $bogomips[1][0];
            if (\count($cache[0]) > 0) {
                $info['l2cache'] = \trim($cache[1][0]);
            }
            $info['flags'] = $flags[2][0];
        }
        return $info;
    }

    /**
     * 获取信息自最后一次开机以来持续运行的时间
     *
     * @return array
     */
    public static function getUptimeInfo()
    {
        if (!$content = self::readSysInfo('/proc/uptime')) {
            return [];
        }

        $arr     = \explode(' ', \implode('', $content));
        $uptime  = \trim($arr[0]);
        $minutes = $uptime / 60;
        $hours   = $minutes / 60;
        $days    = \floor($hours / 24);
        $hours   = \floor($hours - ($days * 24));
        $minutes = \floor($minutes - ($days * 60 * 24) - ($hours * 60));
        $info = [
            'days'      => $days > 0 ? $days : 0,
            'hours'     => $hours > 0 ? $hours : 0,
            'minutes'   => $minutes > 0 ? $minutes : 0,
            'uptime'    => $uptime
        ];
        return $info;
    }

    /**
     * 获取温度信息
     *
     * @return array
     */
    public static function getTemperatureInfo()
    {
        if (!$content = self::readSysInfo('/sys/class/thermal/thermal_zone0/temp')) {
            return [];
        }

        $info = [
            'cpu' => floatval($content[0]) / 1000,
        ];
        return $info;
    }

    /**
     * 获取内存使用信息
     *
     * @return array
     */
    public static function getMemoryUsage()
    {
        if (!$content = self::readSysInfo('/proc/meminfo')) {
            return [];
        }

        $str = \implode('', $content);
        \preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        \preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);

        $info = [];
        $info['memTotal']             = \round($buf[1][0] / 1024, 2);
        $info['memFree']              = \round($buf[2][0] / 1024, 2);
        $info['memBuffers']           = \round($buffers[1][0] / 1024, 2);
        $info['memCached']            = \round($buf[3][0] / 1024, 2);
        $info['memUsed']              = \round($info['memTotal'] - $info['memFree'] - $info['memBuffers'] - $info['memCached'], 2);
        $info['memUsedPercent']       = \floatval($info['memTotal']) ? \round($info['memUsed'] / $info['memTotal'] * 100, 2) : 0;
        $info['memBuffersPercent']    = \floatval($info['memTotal']) ? \round($info['memBuffers'] / $info['memTotal'] * 100, 2) : 0;
        $info['memCachedPercent']     = \floatval($info['memTotal']) ? \round($info['memCached'] / $info['memTotal'] * 100, 2) : 0;

        $info['swapTotal']            = \round($buf[4][0] / 1024, 2);
        $info['swapFree']             = \round($buf[5][0] / 1024, 2);
        $info['swapUsed']             = \round($info['swapTotal'] - $info['swapFree'], 2);
        $info['swapPercent']          = \floatval($info['swapTotal']) ? \round($info['swapUsed'] / $info['swapTotal'] * 100, 2) : 0;

        foreach ($info as $key => $value) {
            if (\strpos($key, 'Percent')) {
                continue;
            }
            $info[$key] = $value < 1024 ? $value . ' MB' :  \round($value / 1024, 3) . ' GB';
        }
        return $info;
    }

    /**
     * 获取系统平均负载信息
     *
     * @return void
     */
    public static function getLoadAvgInfo()
    {
        if ($content = self::readSysInfo('/proc/loadavg')) {
            $arr = \explode(' ', \implode('', $content));
            $arr = \array_chunk($arr, 3);
            return $arr[0];
        }

        if (\function_exists('\\sys_getloadavg')) {
            if ($arr = \sys_getloadavg()) {
                return \array_map(function ($item) {
                    return \sprintf('%.2f', $item);
                }, $arr);
            }
        }

        return [];
    }

    /**
     * 获取操作系统名称
     *
     * @return string
     */
    public static function getOsReleaseName()
    {
        foreach (['redhat', 'centos', 'system'] as $name) {
            if ($content = self::readSysInfo("/etc/$name-release")) {
                return \trim(\array_shift($content));
            }
        }

        if ($content = self::readSysInfo('/etc/os-release')) {
            $arr = \parse_ini_string(\implode('', $content));
            if (isset($arr['DISTRIB_DESCRIPTION'])) {
                return $arr['DISTRIB_DESCRIPTION'];
            }
            if (isset($arr['PRETTY_NAME'])) {
                return $arr['PRETTY_NAME'];
            }
            if (isset($arr['NAME']) && isset($arr['VERSION'])) {
                return $arr['NAME'] . ' ' . $arr['VERSION'];
            }
        }

        return \php_uname('s') . ' ' . \php_uname('r');
    }

    /**
     * 获取主板信息
     *
     * @return array
     */
    public static function getMainBoardInfo()
    {
        $info = [];
        if ($content = self::readSysInfo('/sys/class/dmi/id/bios_vendor', FILE_IGNORE_NEW_LINES)) {
            $info['BoardBiosVendor'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/bios_version', FILE_IGNORE_NEW_LINES)) {
            $info['BoardBiosVersion'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/bios_date', FILE_IGNORE_NEW_LINES)) {
            $info['BoardBiosDate'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/board_vendor', FILE_IGNORE_NEW_LINES)) {
            $info['boardVendor'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/board_name', FILE_IGNORE_NEW_LINES)) {
            $info['boardName'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/board_version', FILE_IGNORE_NEW_LINES)) {
            $info['boardVersion'] = \array_shift($content);
        }
        if ($content = self::readSysInfo('/sys/class/dmi/id/product_name', FILE_IGNORE_NEW_LINES)) {
            $info['productName'] = \array_shift($content);
        }

        if ($content = @\scandir('/dev/disk/by-id')) {
            if ($names = \array_filter($content, function ($k) {
                return $k[0] != '.' && \strpos($k, 'DVD-ROM') === false;
            })) {
                $parts = \explode('_', \array_shift($names));
                $parts = \explode('-', \array_shift($parts), 2);
                $info['diskVendor'] = \strtoupper($parts[0]);
                $info['diskModel'] = $parts[1];
            }
        }

        return $info;
    }

    /**
     * 获取硬盘使用信息
     *
     * @return array
     */
    public static function getDiskUsage()
    {
        $total = round(disk_total_space('.') / (1024 * 1024 * 1024), 2);
        $free  = round(disk_free_space('.') / (1024 * 1024 * 1024), 2);
        $used  = round($total - $free, 2);
        $info = [
            'total' => $total . 'G',
            'free'  => $free . 'G',
            'used'  => $used . 'G',
        ];
        if (floatval($total)) {
            $info['percent'] = round($used / $total * 100, 2) . '%';
        }

        return $info;
    }

    /**
     * 获取网卡适配器的统计信息
     *
     * @return array
     */
    public static function getNetworkUsage()
    {
        if (!$content = self::readSysInfo('/proc/net/dev')) {
            return [];
        }

        $info = [];
        for ($i = 2, $cnt = \count($content); $i < $cnt; $i++) {
            $parts = \preg_split('/\s+/', \trim($content[$i]));
            $dev   = \trim($parts[0], ':');
            $info[$dev] = [
                'receives'  => self::humanFilesize($parts[1]),
                'receive'   => \intval($parts[1]),
                'transmits' => self::humanFilesize($parts[9]),
                'transmit'  => \intval($parts[9]),
            ];
        }

        return $info;
    }

    /**
     * 获取网络ARP表信息(网上邻居)
     *
     * @return array
     */
    public static function getNetworkArpInfo()
    {
        if (!$content = self::readSysInfo('/proc/net/arp')) {
            return [];
        }

        $info = [];
        $seen = [];
        for ($i = 1; $i < \count($content); $i++) {
            $parts = \preg_split('/\s+/', $content[$i]);
            if ('0x2' == $parts[2] && !isset($seen[$parts[3]])) {
                $seen[$parts[3]] = true;
                $info[$parts[0]] = [
                    'mac'    => $parts[3],
                    'type'   => $parts[1] == '0x1' ? 'Ethernet' : $parts[1],
                    'device' => $parts[5],
                ];
            }
        }

        return $info;
    }
}
