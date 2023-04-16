<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\console\command\make;

use funsent\console\build\Output;
use funsent\application\Application;

/**
 * make命令
 * @package funsent\console\command\make
 */
class Make
{
    use Output;

    /**
     * 创建应用控制器，如：php funsent make:controller home.photo
     * @param string $args 模块.控制器
     * @param string $template
     * @return boolean|integer
     */
    public function controller($args, $template = 'controller')
    {
        $info = explode('.', $args);
        if (count($info) < 2) {
            $this->error('error command args, (e.g. module.controller)');
        }
        $module = $info[0];
        $controller = ucfirst($info[1]);
        $file = sprintf('%s/%s/controller/%s.php', Application::path(), $module, $controller);
        if (!is_dir(dirname($file))) {
            if (!mkdir(dirname($file), 0755, true)) {
                $this->error('controller directory create failed');
            }
        }
        if (is_file($file)) {
            $this->error('controller already exist');
        }
        $template = strtolower($template) == 'resource' ? 'resource' : 'controller';
        $html = file_get_contents(sprintf('%s/template/%s.tpl', __DIR__, $template));
        $html = str_replace(
            ['{{app}}', '{{module}}', '{{controller}}'],
            [Application::name(), $module, $controller],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建资源控制器，如：php funsent make:resource home.photo
     * @param string $args 模块.控制器
     * @return boolean|integer
     */
    public function resource($args)
    {
        return $this->controller($args, 'resource');
    }

    /**
     * 创建模型，如：
     *      php funsent make:model photo // 创建公共模型
     *      php funsent make:model home.photo // 创建模块模型
     * @param string $args 模块.模型
     * @return boolean|integer
     */
    public function model($args)
    {
        $info = explode('.', $args);
        if (count($info) == 1) { // 公共模型
            $model = ucfirst($info[0]);
            $file = sprintf('%s/common/model/%s.php', Application::path(), $model);
            if (!is_dir(dirname($file))) {
                if (!mkdir(dirname($file), 0755, true)) {
                    $this->error('model directory create failed');
                }
            }
            if (is_file($file)) {
                $this->error('model already exist');
            }
            $html = file_get_contents(__DIR__ . '/template/model/model.tpl');
            $html = str_replace(
                ['{{app}}', '{{model}}', '{{table}}'],
                [Application::name(), $model, strtolower($model)],
                $html
            );
        } else { // 模块模型
            $module = $info[0];
            $model = ucfirst($info[1]);
            $file = sprintf('%s/%s/model/%s.php', Application::path(), $module, $model);
            if (!is_dir(dirname($file))) {
                if (!mkdir(dirname($file), 0755, true)) {
                    $this->error('model directory create failed');
                }
            }
            if (is_file($file)) {
                $this->error('model already exist');
            }
            $html = file_get_contents(__DIR__ . '/template/model/module.model.tpl');
            $html = str_replace(
                ['{{app}}', '{{module}}', '{{model}}', '{{table}}'],
                [Application::name(), $module, $model, strtolower($model)],
                $html
            );
        }
        return file_put_contents($file, $html);
    }

    /**
     * 创建应用数据迁移，如：
     *      php funsent make:migration CreateArticleTable --create=article
     *      php funsent make:migration ChangeArticleTable --alter=article
     * @param string $class
     * @param string $args
     * @return boolean|integer
     */
    public function migration($class, $args)
    {
        $path = sprintf('%s/common/database/migrations', Application::path());
        if (!is_dir($path)) {
            $this->error('migration file not found');
        }
        foreach (glob($path . '/*.php') as $file) {
            if (stristr($file, $class)) {
                $this->error('migration file already exist');
            }
        }
        $info = explode('=', $args);
        $prefix = strtolower($info[0]);
        $table = strtolower($info[1]);
        $file = sprintf('%s/%s_%s.php', $path, date('ymdHis'), $class);
        if ($prefix == '--create') { // 创建表
            $html = file_get_contents(__DIR__ . '/template/migration.create.tpl');
            $html = str_replace(
                ['{{app}}', '{{table}}', '{{class}}'],
                [Application::name(), $table, $class],
                $html
            );
            return file_put_contents($file, $html);
        } elseif ($prefix == '--alter') { // 修改表
            $html = file_get_contents(__DIR__ . '/template/migration.alter.tpl');
            $html = str_replace(
                ['{{app}}', '{{table}}', '{{class}}'],
                [Application::name(), $table, $class],
                $html
            );
            return file_put_contents($file, $html);
        }
    }

    /**
     * 创建数据填充，如：php funsent make:seed UsersTableSeeder
     * @param string $class
     * @return boolean|integer
     */
    public function seed($class)
    {
        $path = sprintf('%s/common/database/seeds', Application::path());
        if (!is_dir($path)) {
            $this->error('seeds file not found');
        }
        foreach (glob($path . '/*.php') as $file) {
            $fileInfo = pathinfo($file);
            $basename = strtolower(substr($fileInfo['basename'], 0, strlen($class)));
            if ($basename == strtolower($class)) {
                $this->error('seeds file already exist');
            }
        }
        $file = sprintf('%s/%s_%s.php', $path, date('ymdHis'), $class);
        $html = file_get_contents(__DIR__ . '/template/seeder.tpl');
        $html = str_replace(
            ['{{app}}', '{{class}}'],
            [Application::name(), $class],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建视图扩展解析标签，如：php funsent make:tag Common
     * @param string $name
     * @return boolean|integer
     */
    public function tag($name)
    {
        $path = sprintf('%s/common/tag', Application::path());
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('tag directory create failed');
            }
        }
        $name = ucfirst($name);
        $file = sprintf('%s/%s.php', $path, $name);
        if (is_file($file)) {
            $this->error('tag file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/tag.tpl');
        $html = str_replace(
            ['{{app}}', '{{name}}'],
            [Application::name(), $name],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建中间件，如：php funsent make:middleware Auth
     * @param string $name
     * @return boolean|integer
     */
    public function middleware($name)
    {
        $path = sprintf('%s/common/middleware', Application::path());
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('middleware directory create failed');
            }
        }
        $name = ucfirst($name);
        $file = sprintf('%s/%s.php', $path, $name);
        if (is_file($file)) {
            $this->error('middleware file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/middleware.tpl');
        $html = str_replace(
            ['{{app}}', '{{name}}'],
            [Application::name(), $name],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 重置应用密钥，如：php funsent make:key
     * @return boolean|integer
     */
    public function key()
    {
        $key = md5(mt_rand(1, 99999) . time()) . md5(mt_rand(1, 99999) . time());
        $file = sprintf('%s/common/config/security.php', Application::path());
        $content = file_get_contents($file);
        $content = preg_replace('/(.*("|\')\s*key\s*\2\s*=>\s*)(.*)/im', "\\1'$key',", $content);
        return file_put_contents($file, $content);
    }

    /**
     * 创建服务，如：php funsent make:service alipay
     * @param string $name
     * @return void
     */
    public function service($name)
    {
        $path = sprintf('%s/common/service/%s', Application::path(), lcfirst($name));
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('service directory create failed');
            }
        }
        $name = ucfirst($name);
        $tpls = [
            __DIR__ . '/template/service/service.tpl',
            __DIR__ . '/template/service/service.facade.tpl',
            __DIR__ . '/template/service/service.provider.tpl',
        ];
        foreach ($tpls as $tpl) {
            $html = str_replace(
                ['{{app}}', '{{name}}', '{{lower_name}}'],
                [Application::name(), $name, strtolower($name)],
                file_get_contents($tpl)
            );
            if (false !== strpos($tpl, 'facade')) {
                $file = sprintf('%s/%sFacade.php', $path, $name);
            } else if (false !== strpos($tpl, 'provider')) {
                $file = sprintf('%s/%sProvider.php', $path, $name);
            } else {
                $file = sprintf('%s/%s.php', $path, $name);
            }
            file_put_contents($file, $html);
        }
    }

    /**
     * 创建请求，如：php funsent make:request User
     * @param string $name
     * @return boolean|integer
     */
    public function request($name)
    {
        $path = sprintf('%s/common/request', Application::path());
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('request directory create failed');
            }
        }
        $name = rtrim($name, 'Request');
        $file = sprintf('%s/%sRequest.php', $path, $name);
        if (is_file($file)) {
            $this->error('request file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/request.tpl');
        $html = str_replace(
            ['{{app}}','{{name}}'],
            [Application::name(), $name],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建基于模型的数据仓库，如：php funsent make:repository User
     * @param string $name
     * @return boolean|integer
     */
    public function repository($name)
    {
        $path = sprintf('%s/common/repository', Application::path());
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('repository directory create failed');
            }
        }
        if (strtolower(substr($name, -19)) == 'repository') {
            $name = rtrim($name, 'Repository');
        }
        $file = sprintf('%s/%sRepository.php', $path, $name);
        if (is_file($file)) {
            $this->error('repository file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/repository/repository.tpl');
        $html = str_replace(
            ['{{app}}','{{name}}'],
            [Application::name(), $name],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建基于模型数据仓库的扩展查询规则，如：php funsent make:rule User
     * @param string $name
     * @return boolean|integer
     */
    public function rule($name)
    {
        $path = sprintf('%s/common/repository/rule', Application::path());
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error('rule directory create failed');
            }
        }
        if (strtolower(substr($name, -4)) == 'rule') {
            $name = rtrim($name, 'Rule');
        }
        $file = sprintf('%s/%sRule.php', $path, $name);
        if (is_file($file)) {
            $this->error('rule file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/repository/rule.tpl');
        $html = str_replace(
            ['{{app}}','{{name}}'],
            [Application::name(), $name],
            $html
        );
        return file_put_contents($file, $html);
    }

    /**
     * 创建单元测试，如：php funsent make:test login --feature
     * @param string $name 类名称
     * @param string $type 测试类型
     * @return boolean
     */
    public function test($name, $type = '--feature')
    {
        $path = sprintf('%s/test', Application::path());
        $name = ucfirst($name);
        switch ($type) {
            case '--feature':
                $file = sprintf('%s/feature/%sTestCase.php', $path, $name);
                break;
            case '--unit':
                $file = sprintf('%s/unit/%sTestCase.php', $path, $name);
                break;
            default:
                $this->error('parameter error');
        }
        if (!is_dir(dirname($file))) {
            if (!mkdir(dirname($file), 0755, true)) {
                $this->error('test directory create failed');
            }
        }
        if (is_file($file)) {
            $this->error('test file already exist');
        }
        $html = file_get_contents(__DIR__ . '/template/test.tpl');
        $html = str_replace(
            ['{{app}}', '{{mode}}', '{{name}}'],
            [Application::name(), trim($type, '--'), $name],
            $html
        );
        return file_put_contents($file, $html);
    }
}
