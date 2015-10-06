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

		// セットアップ状態を検査する
		if ( ! check_epgrec_env() && $this->getControllerName() != 'install' )
		{
			$this->setNextPage('install');
			return;
		}
		else if ( ! ModelBase::isConnect() && $this->getControllerName() != 'install' )
		{
			$this->setNextPage('install', 'step2');
			return;
		}
	}
}
?>