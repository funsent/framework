<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\request;

use funsent\validater\Validater;
use funsent\request\build\FormRequest;

/**
 * {{name}}请求
 * @package {{app}}\common\request
 */
class {{name}}Request extends FormRequest
{
    /**
     * 权限认证
     * @return boolean
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 验证规则，规则参数同Validate服务组件
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
