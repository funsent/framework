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
 * Trait SoftDeletes
 * @package funsent\model\build
 */
trait SoftDeletes
{
    /**
     * 软删除
     * @return mixed
     */
    public function delete()
    {
        array_walk($this->dates, function ($value, $key) {
            $this->$value = time();
        });
        return $this->save();
    }

    /**
     * 恢复软删除
     * @return mixed
     */
    public function restore()
    {
        array_walk($this->dates, function ($value, $key) {
            $this->$value = 0;
        });
        return $this->save();
    }

    /**
     * 真实删除数据
     * @return mixed
     */
    public function forceDelete()
    {
        return parent::delete();
    }
}
