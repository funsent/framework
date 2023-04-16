<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\model;

use funsent\model\Model;

/**
 * {{model}}模型
 * @package {{app}}\common\model
 */
class {{model}} extends Model
{
	/**
	 * 数据表
	 * @var string
	 */
	protected $table        = '{{table}}';

	/**
	 * 允许填充的字段
	 * @var array
	 */
	protected $allowFill    = [];

	/**
	 * 禁止填充的字段
	 * @var array
	 */
	protected $denyFill     = [];

	/**
	 * 自动验证的字段
	 * @var array
	 */
	protected $validate     = [
		// ['字段名','验证方法','提示信息',验证条件,验证时间]
	];

	/**
	 * 自动完成的字段
	 * @var array
	 */
	protected $auto         = [
		// ['字段名','处理方法','方法类型',验证条件,验证时机]
	];

	/**
	 * 自动过滤的字段
	 * @var array
	 */
    protected $filter       = [
        // [表单字段名,过滤条件,处理时间]
    ];

	/**
	 * 自动创建和更新时间戳
	 * 需要表中存在 created_at、updated_at 字段
	 * @var array
	 */
	protected $timestamps   = false;
}
