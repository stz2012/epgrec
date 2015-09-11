<?php
/**
 * 共通コントローラクラス
 * @package ControllerBase
 * @subpackage CommonController
 */
class CommonController extends ControllerBase
{
	/**
	 * 前処理
	 */
	public function preAction()
	{
		// 基底クラスの処理を呼出
		parent::preAction();
		
		// 設定ファイルの有無を検査する
		if ( ! file_exists( INSTALL_PATH."/settings/config.xml") )
		{
			$this->setNextPage('install');
			return;
		}
		else if ( ! ModelBase::isConnect() )
		{
			$this->setNextPage('install', 'step2');
			return;
		}
	}
}
?>