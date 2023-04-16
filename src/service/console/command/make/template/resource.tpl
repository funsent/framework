<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\{{module}}\controller;

/**
 * {{controller}}控制器
 * @package {{app}}\{{module}}\controller
 */
class {{controller}}
{
    /**
     * 索引 /photos
     * @method GET
     * @return mixed
     */
    public function index()
    {
        return 'index';
    }

    /**
     * 新增界面 /photos/create
     * @method GET
     * @return mixed
     */
    public function create()
    {
        return 'create';
    }

    /**
     * 保存数据 /photos
     * @method POST
     * @return mixed
     */
    public function store()
    {
        return 'store';
    }

    /**
     * 显示 /photos/{id}
     * @method GET
     * @return mixed
     */
    public function show($id)
    {
        return 'show';
    }

    /**
     * 修改界面 /photos/{id}/edit
     * @method GET
     * @return mixed
     */
    public function edit($id)
    {
        return 'edit';
    }

    /**
     * 更新 /photos/{id}
     * @method PUT
     * @return mixed
     */
    public function update($id)
    {
        return 'update';
    }

    /**
     * 删除 /photos/{id}
     * @method DELETE
     * @return mixed
     */
    public function destroy($id)
    {
        return 'destroy';
    }
}
