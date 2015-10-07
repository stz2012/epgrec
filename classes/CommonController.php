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
		if ( ! check_epgrec_env() && $this->controller != 'install' )
		{
			$this->setNextPage('install');
			return;
		}
		else if ( ! ModelBase::isConnect() && $this->controller != 'install' )
		{
			$this->setNextPage('install', 'step2');
			return;
		}

		// パラメータ未設定の場合、強制ページ遷移
		if ($this->request->getSession('login_data.user_id') == "")
		{
			if ( ! (($this->controller == 'index' && $this->action == 'index') || $this->controller == 'install') )
			{
				$this->setNextPage('index');
				return;
			}
		}
	}
}
?>