#!/usr/bin/php -q
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');
// コマンドライン起動か判別する
if ( ! (isset($argv[0]) && __FILE__ === realpath($argv[0])) )
	exit;

// 言語設定、内部エンコーディングを指定する
mb_language("japanese");
mb_internal_encoding("UTF-8");

if (count($argv) == 3)
	UtilSQLite::outEventLog($argv[1], $argv[2]);
?>
