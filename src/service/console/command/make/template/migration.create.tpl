<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace {{app}}\common\database\migrations;

use funsent\schema\Schema;
use funsent\schema\build\Migration;
use funsent\schema\build\Blueprint;

/**
 * {{class}}数据迁移，创建表
 * @package {{app}}\common\database\migrations
 */
class {{class}} extends Migration
{
    /**
     * 执行
     * @return void
     */
	public function up()
    {
		Schema::create('{{table}}', function (Blueprint $table) {
		    $table->increment('id');
            $table->timestamps();
        });
    }

    /**
     * 回滚
     * @return void
     */
    public function down()
    {
        Schema::drop('{{table}}');
    }
}
