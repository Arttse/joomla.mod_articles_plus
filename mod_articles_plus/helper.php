<?php
/**
 * @copyright      Copyright (C) 2016 Nikita «Arttse» Bystrov. All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita «Arttse» Bystrov
 */

defined ( '_JEXEC' ) or die;

class modArticlesPlusHelper {

    /**
     * All params of module
     *
     * @var object
     */
    public $params;

    /**
     * Data of module
     *
     * @var object
     */
    public $module;


    /**
     * Initialization.
     *
     * @param $module - data module
     * @param $params - module params
     */
    function __construct ( $module, $params )
    {
        $this->module = $module;
        $this->params = $params;
    }
}