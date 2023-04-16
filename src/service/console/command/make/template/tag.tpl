<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\tag;

use funsent\view\contract\Tag;

/**
 * {{name}}视图标签
 * @package {{app}}\common\tag
 */
class {{name}}Tag extends Tag
{
	/**
	 * 标签声明
	 * @var array
	 */
	public $tags = [
		'line' => ['block' => false],
		'tag'  => ['block' => true, 'level' => 4],
	];

	/**
     * line 标签
     * @return string
     */
	public function _line($attr, $content, &$view)
	{
		return 'link标签 测试内容';
	}

	/**
	 * tag 标签
	 * @return string
	 */
	public function _tag($attr, $content, &$view)
	{
		return 'tag标签 测试内容';
	}
}
