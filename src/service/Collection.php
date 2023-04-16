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

namespace funsent\support;

use funsent\contract\Jsonable;
use funsent\contract\Arrayable;
use funsent\contract\Macroable;

use HigherOrderCollectionProxy;
use IteratorAggregate;
use JsonSerializable;
use CachingIterator;
use ArrayIterator;
use Traversable;
use ArrayAccess;
use Exception;
use Countable;

/**
 * 集合处理类
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable, Jsonable, Arrayable
{
    use Macroable;

    /**
     * 数据项
     * 
     * @var array
     */
    protected $items = [];

    /**
     * 可被代理的方法
     *
     * @var array
     */
    protected static $proxies = [
        'average', 'avg', 'contains', 'each', 'every', 'filter', 'first', 'flatMap',
        'map', 'partition', 'reject', 'sortBy', 'sortByDesc', 'sum',
    ];

    /**
     * 创建新的集合
     *
     * @param mixed $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * 创建新的集合实例，如果集合实例不存在的话
     *
     * @param mixed $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * 通过调用给定次数的回调来创建一个新集合
     *
     * @param int $number
     * @param callable $callback
     * @return static
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static;
        }

        if (is_null($callback)) {
            return new static(range(1, $number));
        }

        return (new static(range(1, $number)))->map($callback);
    }

    /**
     * 获取集合中的所有数据项
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * 获取给定键的平均值
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function avg($callback = null)
    {
        if ($count = $this->count()) {
            return $this->sum($callback) / $count;
        }
    }

    /**
     * avg方法的别名
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * 获取给定键的中值
     *
     * @param null $key
     * @return mixed
     */
    public function median($key = null)
    {
        $count = $this->count();

        if ($count == 0) {
            return;
        }

        $values = with(isset($key) ? $this->pluck($key) : $this)->sort()->values();
        $middle = (int) ($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([
            $values->get($middle - 1),
            $values->get($middle),
        ]))->average();
    }

    /**
     * 获取给定键的模式
     *
     * @param mixed $key
     * @return array|null
     */
    public function mode($key = null)
    {
        $count = $this->count();

        if ($count == 0) {
            return;
        }

        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new self;

        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });

        $sorted = $counts->sort();
        $highestValue = $sorted->last();

        return $sorted->filter(function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();
    }

    /**
     * 将数据项集合转成单个数组
     *
     * @return static
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * 确定集合中是否存在数据项
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() == 1) {
            if ($this->useAsCallable($key)) {
                return !is_null($this->first($key));
            }
            return in_array($key, $this->items);
        }

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->contains($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * 使用严格比较确定集合中是否存在数据项
     *
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (func_num_args() == 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }

        return in_array($key, $this->items, true);
    }

    /**
     * 交叉加入给定的列表，返回所有可能的排列
     *
     * @param mixed ...$lists
     * @return static
     */
    public function crossJoin(...$lists)
    {
        return new static(Arr::crossJoin(
            $this->items,
            ...array_map([$this, 'getArrayableItems'], $lists)
        ));
    }

    /**
     * 获取集合中不存在于给定项中的数据项
     *
     * @param mixed $items
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 获取集合中的数据项，其键不存在于给定的数据项中
     *
     * @param mixed $items
     * @return static
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 获取集合中的数据项，其键和值不存在于给定数据项中
     *
     * @param mixed $items
     * @return static
     */
    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 对每个数据项执行回调
     *
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * 对每个嵌套数据项块执行回调
     *
     * @param callable $callback
     * @return static
     */
    public function eachSpread(callable $callback)
    {
        return $this->each(function ($chunk) use ($callback) {
            return $callback(...$chunk);
        });
    }

    /**
     * 确定集合中的所有数据项是否通过给定测试
     *
     * @param string|callable $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (func_num_args() == 1) {
            $callback = $this->valueRetriever($key);
            foreach ($this->items as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }
            return true;
        }

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->every($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * 获取所有数据项，但具有指定键的数据项除外
     *
     * @param mixed $keys
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::except($this->items, $keys));
    }

    /**
     * 在每个项目上运行筛选器
     *
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }
        return new static(array_filter($this->items));
    }

    /**
     * 如果值为true，则应用回调
     *
     * @param bool $value
     * @param callable $callback
     * @param callable $default
     * @return mixed
     */
    public function when($value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this);
        } elseif ($default) {
            return $default($this);
        }
        return $this;
    }

    /**
     * 如果值为false，则应用回调
     *
     * @param bool $value
     * @param callable $callback
     * @param callable $default
     * @return mixed
     */
    public function unless($value, callable $callback, callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * 通过给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->filter($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * 使用严格比较，按给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * 通过给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * 使用严格比较，按给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereInStrict($key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * 通过给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->reject(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * 使用严格比较，按给定的键值对筛选数据项
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereNotInStrict($key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * 获取一个操作检查者回调
     *
     * @param string $key
     * @param string $operator
     * @param mixed $value
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator, $value)
    {
        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);
            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * 从集合中获取第一个数据项
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * 获取集合中数据项的扁平数组
     *
     * @param int $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * 翻转集合中的项目
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * 根据键从集合中移除数据项
     *
     * @param string|array $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }
        return $this;
    }

    /**
     * 根据键从集合中获取数据项
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        return value($default);
    }

    /**
     * 通过字段或使用回调将关联数组分组
     *
     * @param callable|string $groupBy
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int) $groupKey : $groupKey;
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        return new static($results);
    }

    /**
     * 通过字段或使用回调来键入关联数组
     *
     * @param callable|string $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        $keyBy = $this->valueRetriever($keyBy);
        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }
            $results[$resolvedKey] = $item;
        }

        return new static($results);
    }

    /**
     * 确定关键字集合中是否存在数据项
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * 将给定键的值串联为字符串
     *
     * @param string $value
     * @param string $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * 取集合与给定数据项的交集
     *
     * @param mixed $items
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 根据键取集合与给定数据项得交集
     *
     * @param mixed $items
     * @return static
     */
    public function intersectKey($items)
    {
        return new static(array_intersect_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 确定集合是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * 确定集合是否不为空
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * 确定给定值是否可调用，但不能调用字符串
     *
     * @param mixed $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * 获取集合数据项的键
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * 获取集合中最后的数据项
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * 根据给出的键获取值
     *
     * @param string|array $value
     * @param string|null $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * 在每个数据项上运行一个映射
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * 在每个嵌套的数据项中运行映射
     *
     * @param callable $callback
     * @return static
     */
    public function mapSpread(callable $callback)
    {
        return $this->map(function ($chunk) use ($callback) {
            return $callback(...$chunk);
        });
    }

    /**
     * 在数据项上运行分组映射
     * 回调应该返回具有单个键/值对的关联数组
     *
     * @param callable $callback
     * @return static
     */
    public function mapToGroups(callable $callback)
    {
        $groups = $this->map($callback)->reduce(function ($groups, $pair) {
            $groups[key($pair)][] = reset($pair);
            return $groups;
        }, []);

        return (new static($groups))->map([$this, 'make']);
    }

    /**
     * 在每个数据项上运行一个关联映射
     * 回调应该返回具有单个键/值对的关联数组
     *
     * @param callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    /**
     * 映射一个集合并将结果转成单一数组
     *
     * @param callable $callback
     * @return static
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * 获取给定键的最大值
     *
     * @param callablestring|null $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * 将集合与给定数据项合并
     *
     * @param mixed $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * 通过对键使用此集合并为其值使用另一个集合来创建集合
     *
     * @param mixed $values
     * @return static
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * 将集合与给定数据项合并
     *
     * @param mixed $items
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * 获取给定键的最小值
     *
     * @param callablestring|null $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * 创建一个由每个n个元素组成的新集合
     *
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        $new = [];
        $position = 0;

        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            $position++;
        }

        return new static($new);
    }

    /**
     * 获取具有指定键的数据项
     *
     * @param mixed $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
    }

    /**
     * 通过将集合切片成一个较小的集合来实现“分页”
     *
     * @param int $page
     * @param int $perPage
     * @return static
     */
    public function forPage($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * 使用给定的回调或键将集合划分为两个数组
     *
     * @param callable|string $callback
     * @return static
     */
    public function partition($callback)
    {
        $partitions = [new static, new static];
        $callback = $this->valueRetriever($callback);

        foreach ($this->items as $key => $item) {
            $partitions[(int) !$callback($item)][$key] = $item;
        }

        return new static($partitions);
    }

    /**
     * 将集合传递给给定的回调并返回结果
     *
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * 从集合中获取并删除最后一项
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * 将数据项推送到集合的开头
     *
     * @param mixed $value
     * @param mixed $key
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }

    /**
     * 将数据项推送到集合的末尾
     *
     * @param mixed $value
     * @return $this
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    /**
     * 将所有给定数据项推送到集合中
     *
     * @param \Traversable $source
     * @return self
     */
    public function concat($source)
    {
        $result = new static($this);

        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }

    /**
     * 从集合中获取并删除项
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * 根据键将数据项放入集合中
     *
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * 从集合中随机获取一个或随机数量的数据项
     *
     * @param intnull $number
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($number = null)
    {
        if (is_null($number)) {
            return Arr::random($this->items);
        }

        return new static(Arr::random($this->items, $number));
    }

    /**
     * 将集合减少到单个值
     *
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * 创建所有未通过给定真理测试的元素的集合
     *
     * @param callablemixed $callback
     * @return static
     */
    public function reject($callback)
    {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }

        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }

    /**
     * 数据项倒序排列
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * 在集合中搜索给定的值，如果成功返回相应的键
     *
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * 从集合中获取和删除第一个数据项
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * 在集合中混洗这些数据项
     *
     * @param int $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        $items = $this->items;

        if (is_null($seed)) {
            shuffle($items);
        } else {
            srand($seed);
            usort($items, function () {
                return rand(-1, 1);
            });
        }

        return new static($items);
    }

    /**
     * 将底层集合数组切片
     *
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * 将集合分割成一定数量的组
     *
     * @param int $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static;
        }

        $groupSize = ceil($this->count() / $numberOfGroups);
        return $this->chunk($groupSize);
    }

    /**
     * 将底层集合数组分块
     *
     * @param int $size
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * 用回调对每个数据项进行排序
     *
     * @param callablenull $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }

    /**
     * 使用给定的回调对集合进行排序
     *
     * @param callablestring $callback
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];

        $callback = $this->valueRetriever($callback);

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * 使用给定的回调按降序排序集合
     *
     * @param callablestring $callback
     * @param int $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * 拼接底层集合数组的一部分
     *
     * @param int $offset
     * @param intnull $length
     * @param mixed $replacement
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * 获取给定值的和
     *
     * @param callablestring|null $callback
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback);

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * 取第一个或最后 {$limit} 数据项
     *
     * @param int $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * 将集合传递给给定的回调，然后返回它
     *
     * @param callable $callback
     * @return $this
     */
    public function tap(callable $callback)
    {
        $callback(new static($this->items));
        return $this;
    }

    /**
     * 使用回调转换集合中的每个数据项
     *
     * @param callable $callback
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

    /**
     * 仅返回集合数组中唯一的数据项
     *
     * @param string|callable|null $key
     * @param bool $strict
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $callback = $this->valueRetriever($key);
        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }
            $exists[] = $id;
        });
    }

    /**
     * 使用严格的比较，仅返回集合数组中唯一的数据项
     * 
     * @param string|callable|null $key
     * @return static
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * 重置底层数组的键
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * 获取一个检索回调的值
     *
     * @param string $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * 将集合与一个或多个数组一起压缩
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param mixed...$items
     * @return static
     */
    public function zip($items)
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());

        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayableItems);

        return new static(call_user_func_array('array_map', $params));
    }

    /**
     * 以普通数组的形式返回数据项的集合
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * 将对象转换为可序列化的JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->items);
    }

    /**
     * 以JSON的形式获取数据项的集合
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * 获取数据项的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * 获取缓存器实例
     *
     * @param int $flags
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * 统计集合中的数据项数量
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * 从此集合获取基本支持集合实例
     *
     * @return \funsent\supports\Collection
     */
    public function toBase()
    {
        return new self($this);
    }

    /**
     * 是否存在数据项
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * 获取数据项
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * 设置数据项
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * 移除数据项
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * 将集合转换为其字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * 来自Collection或Arraable的项的结果数组
     *
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        } elseif ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * 将方法添加到代理方法列表中
     *
     * @param string $method
     * @return void
     */
    public static function proxy($method)
    {
        static::$proxies[] = $method;
    }

    /**
     * 动态访问集合代理方法
     *
     * @param string $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (!in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }
        return new HigherOrderCollectionProxy($this, $key);
    }
}
