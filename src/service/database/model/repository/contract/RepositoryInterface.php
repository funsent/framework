<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model\repository\contract;

/**
 * Interface RepositoryInterface
 * @package funsent\model\repository\contract
 */
interface RepositoryInterface
{
    /**
     * 获取所有记录
     * @param array $fields
     * @return mixed
     */
    public function all($fields = ['*']);

    /**
     * 获取分页记录
     * @param integer $page
     * @param array $fields
     * @return mixed
     */
    public function paginate($page = 15, $fields = ['*']);

    /**
     * 新增记录
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * 根据主键更新记录
     * @param array $data
     * @param integer $id
     * @return mixed
     */
    public function update(array $data, $id);

    /**
     * 根据主键删除记录
     * @param integer $id
     * @return mixed
     */
    public function delete($id);

    /**
     * 根据主键获取记录
     * @param integer $id
     * @param array $fields
     * @return mixed
     */
    public function find($id, $fields = ['*']);

    /**
     * 根据字段值获取记录
     * @param string $field
     * @param string $value
     * @param array $fields
     * @return mixed
     */
    public function findBy($field, $value, $fields = ['*']);
}
