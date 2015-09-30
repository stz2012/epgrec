<?php
/*
 * disk info
 */

// epgrec directory
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php';

$settings = Settings::factory();
$disk = INSTALL_PATH . $settings->spool[0];

$param = array();
$param['disk_total'] = disk_total_space($disk);
$param['disk_free']  = disk_free_space($disk);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($param);
?>