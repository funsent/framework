<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\database\seeds;

use funsent\db\Db;
use funsent\schema\build\Seeder;

/**
 * {{class}}数据填充
 * @package {{app}}\common\database\seeds
 */
class {{class}} extends Seeder
{
    /**
     * 执行
     * @return void
     */
	public function up()
    {
		Db::table('news')->insert(['title' => '锋尚云']);
    }

    /**
     * 回滚
     * @return void
     */
    public function down()
    {

    }
}
