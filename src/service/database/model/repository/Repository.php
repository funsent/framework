<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\repository;

use funsent\model\Model;
use funsent\application\Application;
use funsent\error\exception\RuntimeException;
use funsent\model\repository\contract\RuleInterface;
use funsent\model\repository\contract\RepositoryInterface;

/**
 * Class Repository
 * @package funsent\model\repository
 */
abstract class Repository implements RuleInterface, RepositoryInterface
{
    /**
     * 应用实例
     * @var object
     */
    protected $application;

    /**
     * 模型实例
     * @var object
     */
    protected $model;

    /**
     * 查询规则集合
     * @var array
     */
    protected $rules = [];

    /**
     * 跳过查询规则
     * @var boolean
     */
    protected $skipRule = false;

    /**
     * Repository constructor.
     * @param \funsent\application\Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->makeModel();
    }

    /**
     * 获取模型类名
     * @return string
     */
    abstract public function model();

    /**
     * 创建模型
     * @return Model
     * @throws \funsent\error\exception\RuntimeException
     */
    public function makeModel()
    {
        $model = $this->application->make($this->model());
        if (!($model instanceof Model)) {
            $msg = sprintf('Class must extends \\funsent\\model\\Model %s', $this->model());
            throw new RuntimeException($msg);
        }
        return $this->model = $model;
    }

    /**
     * 获取所有记录
     * @param array $fields
     * @return mixed
     */
    public function all($fields = ['*'])
    {
        $this->applyRule();
        return $this->model->get($fields);
    }

    /**
     * 获取分页记录
     * @param integer $page
     * @param array $fields
     * @return mixed
     */
    public function paginate($page = 15, $fields = ['*'])
    {
        $this->applyRule();
        return $this->model->paginate($page, $fields);
    }

    /**
     * 新增记录
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->save($data);
    }

    /**
     * 根据主键更新记录
     * @param array $data
     * @param integer $id
     * @return mixed
     */
    public function update(array $data, $id)
    {
        $model = $this->model->find($id);
        return $model->save($data);
    }

    /**
     * 根据主键删除记录
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        $model = $this->model->find($id);
        return $model->destory();
    }

    /**
     * 根据主键获取记录
     * @param integer $id
     * @param array $fields
     * @return mixed
     */
    public function find($id, $fields = ['*'])
    {
        $this->applyRule();
        return $this->model->field($fields)->find($id);
    }

    /**
     * 根据字段值获取记录
     * @param string $field
     * @param string $value
     * @param array $fields
     * @return mixed
     */
    public function findBy($field, $value, $fields = ['*'])
    {
        $this->applyRule();
        return $this->model->where($field, $value)->field($fields)->first();
    }

    /**
     * 重置规则
     * @return $this
     */
    public function resetRule()
    {
        return $this->skipRule(false);
    }

    /**
     * 是否跳过规则，不执行任何规则
     * @param boolean $status
     * @return $this
     */
    public function skipRule($status = true)
    {
        $this->skipRule = $status;
        return $this;
    }

    /**
     * 获取所有规则
     * @return array
     */
    public function getRule()
    {
        return $this->rules;
    }

    /**
     * 根据规则实例化模型
     * @param Rule $rule
     * @return $this
     */
    public function getByRule(Rule $rule)
    {
        $this->model = $rule->apply($this->model, $this);
        return $this;
    }

    /**
     * 添加规则
     * @param Rule $rule
     * @return $this
     */
    public function pushRule(Rule $rule)
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * 应用所有规则
     * @return $this
     */
    public function applyRule()
    {
        if ($this->skipRule === true) {
            return $this;
        }
        foreach ($this->getRule() as $rule) {
            if ($rule instanceof Rule) {
                $this->model = $rule->apply($this->model, $this);
            }
        }
        return $this;
    }

    /**
     * 调用不存在的实例方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->model, $method], $parameters);
    }
}
