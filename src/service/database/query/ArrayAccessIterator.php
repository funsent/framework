<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\db\query;

/**
 * Trait ArrayAccessIterator
 * @package funsent\db\query
 */
trait ArrayAccessIterator
{
    /**
     * 设置元素
     * @param string $key
     * @param $value
     * @return void
     */
	public function offsetSet($key, $value)
	{
		$this->original[$key] = $value;
	}

    /**
     * 获取元素
     * @param string $key
     * @return null
     */
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

    /**
     * 检测键名是否存在
     * @param string $key
     * @return boolean
     */
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

    /**
     * 删除元素
     * @param $key
     * @return void
     */
	public function offsetUnset($key)
	{
		if (isset($this->original[$key])) {
			unset($this->original[$key]);
		}
	}

    /**
     * 重置，将数组的内部指针指向第一个单元
     * @return void
     */
	public function rewind()
	{
		reset($this->data);
	}

    /**
     * 获取当前元素
     * @return mixed
     */
	public function current()
	{
		return current($this->data);
	}

    /**
     * 移动到下一个
     * @return mixed
     */
	public function next()
	{
		return next($this->data);
	}

    /**
     * 获取键名
     * @return integer|null|string
     */
	public function key()
	{
		return key($this->data);
	}

    /**
     * 检测当前元素是否有效
     * @return mixed
     */
	public function valid()
	{
		return current($this->data);
	}
}
