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
		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'sitetitle',    'システム設定' );
		$this->view->assign( 'post_to',      "{$this->getCurrentUri(false)}/save" );
		$this->view->assign( 'pdo_driver',   $this->model->getPdoDriverOptions() );
	}

	/**
	 * ユーザ一覧
	 */
	public function userListAction()
	{
		$this->view->assign( 'sitetitle' , 'ユーザ一覧' );
		$this->view->assign( 'users',      $this->model->getUserList() );
	}

	/**
	 * ユーザ編集
	 */
	public function userEditAction()
	{
		$user_id = $this->request->getQuery('user_id');
		if ($user_id != '')
		{
			$this->view->assign( 'sitetitle' , 'ユーザ編集' );
			$this->view->assign( 'user_data',  $this->model->getUserInfo($user_id) );
		}
		else
		{
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}/userList" );
			exit;
		}
	}

	/**
	 * ログ表示
	 */
	public function viewLogAction()
	{
		$this->view->assign( 'sitetitle' , 'epgrec動作ログ' );
		$this->view->assign( 'log_types',  UtilSQLite::getLogType() );
		$log_type = $this->request->getPost('log_type');
		if ($log_type != '')
			$this->view->assign( 'events', UtilSQLite::getEventLog($log_type) );
		else
			$this->view->assign( 'logs',   $this->model->getLogList() );
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
			jdialog( '設定が保存されました。', $this->getCurrentUri(false) );
		}
		else
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}" );
		exit;
	}
}
?>