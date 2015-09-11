<?php
	/**
	 * epgrec - フロントエンド
	 */
	require_once dirname(dirname(dirname(__FILE__))).'/config.php';

	/**
	 * 共通変数
	 */
	define('CRYPT_KEY',    'WIchB266vMfXfueQP2YrgcfFBWxjOka0');
	define('SESS_TIMEOUT', '+30 minutes');
	define('ROOT_PATH',    INSTALL_PATH);
	define('HOME_URL',     '/epgrec/');
	define('BASE_URI',     '/epgrec/');

	/**
	 * ディスパッチャの生成
	 */
	$dispatcher = new Dispatcher();
	$dispatcher->setSystemRoot(ROOT_PATH);
	$dispatcher->dispatch();
?>