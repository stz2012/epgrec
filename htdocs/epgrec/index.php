<?php
	/**
	 * epgrec - フロントエンド
	 */
	require_once realpath(dirname(__FILE__).'/../../config.php');

	/**
	 * ディスパッチャの生成
	 */
	$dispatcher = new Dispatcher();
	$dispatcher->setSystemRoot(INSTALL_PATH);
	$dispatcher->dispatch();
?>