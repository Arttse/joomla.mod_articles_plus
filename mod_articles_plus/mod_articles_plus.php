<?php
/**
 * @copyright      Copyright (C) 2016-2018 Nikita Bystrov (Arttse). All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita Bystrov (Arttse)
 */

defined ( '_JEXEC' ) or die;

require_once __DIR__ . '/helper.php';

/** @var object - Object-Helper. For more details see helper.php */
$h = $helper = new modArticlesPlusHelper( $module, $params );

$moduleclass_sfx = htmlspecialchars ( $params->get ( 'moduleclass_sfx' ) );

require JModuleHelper::getLayoutPath ( $module->module, $params->get ( 'layout', 'default' ) );