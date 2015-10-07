<?php
/**
 * epgrec - 設定ページコントローラ
 * @package CommonController
 * @subpackage SettingController
 */
class SettingController extends CommonController
{
	/**
	 * 環境設定
	 */
	public function indexAction()
	{
		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'sitetitle',    '環境設定' );
		$this->view->assign( 'post_to',      "{$this->getCurrentUri(false)}/save" );
		$this->view->assign( 'record_mode',  $this->model->getRecModeOptions() );
	}

	/**
	 * システム設定
	 */
	public function systemAction()
	{
		global $PDO_DRIVER_MAP;
		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'sitetitle',    'システム設定' );
		$this->view->assign( 'post_to',      "{$this->getCurrentUri(false)}/save" );
		$this->view->assign( 'pdo_driver',   $PDO_DRIVER_MAP );
	}

	/**
	 * ログ表示
	 */
	public function viewLogAction()
	{
		$this->view->assign( 'sitetitle' , 'epgrec動作ログ' );
		$this->view->assign( 'logs', $this->model->selectRow('*', $this->model->getFullTblName(LOG_TBL), '', 'logtime DESC') );
	}

	/**
	 * 設定保存
	 */
	public function saveAction()
	{
		$POST_DATA = $this->request->getPost();
		if ($POST_DATA['token'] != '')
		{
			$this->setting->post($POST_DATA);
			$this->setting->save();
			jdialog( '設定が保存されました', HOME_URL );
		}
		else
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}" );
		exit;
	}
}
?>