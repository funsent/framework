<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\build;

/**
 * Trait ArrayIterator
 * @package funsent\model\build
 */
trait ArrayIterator
{
    /**
     * @param $key
     * @param $value
     */
    public function offsetSet($key, $value)
    {
        $this->original[$key] = $value;
        $this->data[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function offsetGet($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * @param $key
     */
    public function offsetUnset($key)
    {
        if (isset($this->original[$key])) {
            unset($this->original[$key]);
        }
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    /**
     *
     */
    function rewind()
    {
        reset($this->data);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @return mixed
     */
    public function valid()
    {
        return current($this->data);
    }
}
