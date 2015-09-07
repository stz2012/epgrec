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
		global $RECORD_MODE;
		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "record_mode", $RECORD_MODE );
		$this->view->assign( "install_path", INSTALL_PATH );
		$this->view->assign( "sitetitle", "環境設定" );
	}

	/**
	 * システム設定
	 */
	public function systemAction()
	{
		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "install_path", INSTALL_PATH );
		$this->view->assign( "sitetitle", "システム設定" );
	}

	/**
	 * ログ表示
	 */
	public function viewLogAction()
	{
		$arr = $this->model->selectRow('*', "{$this->setting->tbl_prefix}".LOG_TBL, '', array(array('logtime', 'DESC')));
		$this->view->assign( "sitetitle" , "epgrec動作ログ" );
		$this->view->assign( "logs", $arr );
	}

	/**
	 * 設定保存
	 */
	public function saveAction()
	{
		$this->setting->post($this->request->getPost());
		$this->setting->save();
		jdialog("設定が保存されました", HOME_URL);
	}
}
?>