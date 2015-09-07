<?php
	/**
	 * epgrec - フロントエンド
	 */
	require_once 'config.php';
	require_once 'epgrecLib.inc.php';
	
	/**
	 * 共通変数
	 */
	define('CRYPT_KEY',    'qdCHKClUuTyQbrCShSIzdqPWD7yqHetF');
	define('SESS_TIMEOUT', '+30 minutes');
	define('ROOT_PATH',    dirname(__FILE__));
	define('LOG_FILEPATH', dirname(dirname(__FILE__)).'/log/');
	define('HOME_URL',     '/epgrec_new/');
	define('BASE_URI',     '/epgrec_new/');
	
	/**
	 * ディスパッチャの生成
	 */
	$dispatcher = new Dispatcher();
	$dispatcher->setSystemRoot(ROOT_PATH);
	$dispatcher->dispatch();
?>