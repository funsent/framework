<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model;

use Iterator;
use ArrayAccess;
use Carbon\Carbon;
use funsent\db\Db;
use funsent\arr\Arr;
use funsent\db\query\Query;
use funsent\model\build\Writer;
use funsent\model\build\Filter;
use funsent\model\build\Relation;
use funsent\model\build\Validater;
use funsent\collection\Collection;
use funsent\model\build\ArrayIterator;

/**
 * 模型基类
 * @package funsent\model
 */
class Model implements ArrayAccess, Iterator
{
    use ArrayIterator, Relation, Validater, Writer, Filter;

    /**
     * 过滤常量
     */
    const FILTER_EXIST          = 1; // 存在字段时过滤
    const FILTER_NOT_EMPTY      = 2; // 值不为空时过滤
    const FILTER_MUST           = 3; // 必须过滤
    const FILTER_EMPTY          = 4; // 值为空时过滤
    const FILTER_NOT_EXIST      = 5; // 不存在字段时过滤

    /**
     * 验证常量
     */
    const VALIDATE_EXIST        = 1; // 存在字段时验证
    const VALIDATE_NOT_EMPTY    = 2; // 值不为空时验证
    const VALIDATE_MUST         = 3; // 必须验证
    const VALIDATE_EMPTY        = 4; // 值为空时验证
    const VALIDATE_NOT_EXIST    = 5; // 不存在字段时验证

    /**
     * 处理时间常量
     * 用于自动验证和自动写入
     */
    const MODEL_INSERT          = 1; // 插入时
    const MODEL_UPDATE          = 2; // 更新时
    const MODEL_INSERT_UPDATE   = 3; // 插入和更新时

    /**
     * 写入常量
     */
    const WRITE_EXIST           = 1; // 存在字段时写入
    const WRITE_NOT_EMPTY       = 2; // 值不为空时写入
    const WRITE_MUST            = 3; // 必须写入
    const WRITE_EMPTY           = 4; // 值为空时写入
    const WRITE_NOT_EXIST       = 5; // 不存在字段时写入

    /**
     * 允许的填充字段
     * @var array
     */
    protected $allowFill = [];

    /**
     * 禁止的填充字段
     * @var array
     */
    protected $denyFill = [];

    /**
     * 模型数据
     * @var array
     */
    protected $data = [];

    /**
     * 构建数据
     * @var array
     */
    protected $original = [];

    /**
     * 表名
     * @var string
     */
    protected $table;

    /**
     * 表主键
     * @var string
     */
    protected $pk;

    /**
     * 字段映射
     * @var array
     */
    protected $map = [];

    /**
     * 时间操作
     * @var boolean
     */
    protected $timestamps = false;

    /**
     * 数据库连接
     * @var object
     */
    protected $connect;

    /**
     * 数据库驱动
     * @var object
     */
    protected $db;

    /**
     * 构造方法
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->setTable($this->table);
        $this->setDb(Db::table($this->table));
        $this->setPk($this->db->getPrimaryKey());
        if (!empty($data)) {
            $this->create($data);
        }
    }

    /**
     * 设置表名
     * @param string $table
     * @return void
     */
    public function setTable($table)
    {
        if (empty($table)) {
            $model = basename(str_replace('\\', '/', get_class($this)));
            $table = strtolower(trim(preg_replace('/([A-Z])/', '_\1\2', $model), '_'));
        }
        $this->table = $table;
    }

    /**
     * 获取表名
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 设置数据库连接
     * @param mixed $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * 设置主键
     * @param mixed $pk
     */
    public function setPk($pk)
    {
        $this->pk = $pk;
    }

    /**
     * 获取主键
     * @return mixed
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * 获取模型数据
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置模型数据，记录信息属性
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 对象数据转为数组
     * @return array
     */
    final public function toArray()
    {
        return $this->data;
    }

    /**
     * 自动写入数据处理
     * @param array $data
     * @throws \Exception
     */
    final private function fieldFillCheck(array $data)
    {
        if (empty($this->allowFill) && empty($this->denyFill)) {
            $data = [];
        }
        // 允许填充的数据
        if (!empty($this->allowFill) && $this->allowFill[0] != '*') {
            $data = Arr::filterKeys($data, $this->allowFill, 0);
        }
        // 禁止填充的数据
        if (!empty($this->denyFill)) {
            if ($this->denyFill[0] == '*') {
                $data = [];
            } else {
                $data = Arr::filterKeys($data, $this->denyFill, 1);
            }
        }
        $this->original = array_merge($data, $this->original);
    }

    /**
     * 批量设置做准备数据
     * @return $this
     */
    final private function create()
    {
        // 更新时设置主键
        if ($this->action() == self::MODEL_UPDATE) {
            $this->original[$this->pk] = $this->data[$this->pk];
        }
        // 修改时间
        if ($this->timestamps === true) {
            $this->original['updated_at'] = Carbon::now(new \DateTimeZone('PRC'));
            // 更新时间设置
            if ($this->action() == self::MODEL_INSERT) {
                $this->original['created_at'] = Carbon::now(new \DateTimeZone('PRC'));
            }
        }
        return $this;
    }

    /**
     * 动作类型
     * @return integer
     */
    final public function action()
    {
        return empty($this->data[$this->pk]) ? self::MODEL_INSERT : self::MODEL_UPDATE;
    }

    /**
     * 更新模型的时间戳
     * @return boolean
     */
    final public function touch()
    {
        if ($this->action() == self::MODEL_UPDATE && $this->timestamps) {
            $data = ['updated_at' => Carbon::now('PRC')];
            return $this->db->where($this->pk, $this->data[$this->pk])->update($data);
        }
        return false;
    }

    /**
     * 保存数据，针对插入和更新操作
     * @param array $data 批量添加的数据
     * @return boolean
     * @throws \Exception
     */
    final public function save(array $data = [])
    {
        $this->fieldFillCheck($data);
        $this->autoFilte();
        $this->autoWrite();
        $this->original = array_merge($this->data, $this->original);
        if (!$this->autoValidate()) {
            return false;
        }
        $this->create();
        $res = null;
        switch ($this->action()) {
            case self::MODEL_UPDATE:
                if ($res = $this->db->update($this->original)) {
                    $this->setData($this->db->find($this->data[$this->pk]));
                }
                break;
            case self::MODEL_INSERT:
                if ($res = $this->db->insertGetId($this->original)) {
                    if (is_numeric($res) && $this->pk) {
                        $this->setData($this->db->find($res));
                    }
                }
                break;
        }
        $this->original = [];
        return $res;
    }

    /**
     * 删除数据
     * 模型数据中存在主键值，则以主键值做删除条件
     * @return boolean
     */
    final public function destory()
    {
        if (!empty($this->data[$this->pk])) {
            if ($this->db->delete($this->data[$this->pk])) {
                $this->setData([]);
                return true;
            }
        }
        return false;
    }

    /**
     * 获取模型数据值
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        if (method_exists($this, $name)) { // 方法获取
            return $this->$name();
        }
    }

    /**
     * 设置模型数据值
     * @param string $name
     * @param minxed $value
     */
    public function __set($name, $value)
    {
        $this->original[$name] = $value;
        $this->data[$name] = $value;
    }

    /**
     * 调用不存在的实例方法时触发
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $res = call_user_func_array([$this->db, $method], $parameters);
        return $this->returnParse($method, $res);
    }

    /**
     * 调用不存在的静态方法时触发，调用数据驱动方法
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([new static(), $method], $parameters);
    }

    /**
     * 返回值解析
     * @param string $method
     * @param mixed $result
     * @return mixed
     */
    protected function returnParse($method, $result)
    {
        if (!empty($result)) {
            switch (strtolower($method)) {
                case 'find':
                case 'first':
                    $instance = new static();
                    return $instance->setData($result);
                case 'get':
                case 'paginate':
                    $collection = Collection::make([]);
                    foreach ($result as $key => $value) {
                        $instance = new static();
                        $collection[$key] = $instance->setData($value);
                    }
                    return $collection;
                default:
                    // 返回值为查询构造器对象时，返回模型实例
                    if ($result instanceof Query) {
                        return $this;
                    }
            }
        }
        return $result;
    }
}
