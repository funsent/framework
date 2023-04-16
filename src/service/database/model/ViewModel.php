<?php

/*
 *--------------------------------------------------------------------------
 * funsent - the php framework for web application
 * Copyright(C)2010-2017 funsent.com Inc. All Rights Reserved.
 *--------------------------------------------------------------------------
 * $Id$
 */

namespace funsent\model;

use funsent\model\Model;

/**
 * 视图模型
 * @package funsent\model
 */
class ViewModel extends Model
{
    /**
     * @var array
     */
    protected $view = [];

    /**
     * @return $this
     */
    public function view()
    {
        if (!empty($this->view)) {
            foreach ($this->view as $table => $view) {
                if ($table !== '_field') {
                    $action = $view['action'];
                    $info = preg_split('/(=|>=|<=)/', $view['on'], 8, PREG_SPLIT_DELIM_CAPTURE);
                    $this->$action($table, $info[0], $info[1], $info[2]);
                } else {
                    $this->field($view);
                }
            }
            return $this;
        }
    }
}
