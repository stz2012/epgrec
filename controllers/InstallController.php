<?php
/**
 * epgrec - インストールページコントローラ
 * @package CommonController
 * @subpackage InstallController
 */
class InstallController extends CommonController
{
	/**
	 * インストールステップ１
	 */
	public function indexAction()
	{
		global $GR_CHANNEL_MAP;
		$this->view->compile_dir = '/tmp';	// 一時的に設定
		$contents = '';

		if ( check_epgrec_env($contents) )
		{
			$contents .= '<br />';
			$contents .= '<p><b>地上デジタルチャンネルの設定確認</b></p>';
			$contents .= '<div>現在、config.phpでは以下のチャンネルの受信が設定されています。受信不可能なチャンネルが混ざっていると番組表が表示できません。</div>';
			$contents .= '<ul>';
			foreach ( $GR_CHANNEL_MAP as $key => $value )
			{
				$contents .= "<li>物理チャンネル {$value}</li>";
			}
			$contents .= '</ul>';
			$contents .= "<p><a href=\"{$this->getCurrentUri(false)}/step2\">以上を確認し次の設定に進む</a></p>";
		}

		$this->view->assign( 'sitetitle', 'インストールステップ１' );
		$this->view->assign( 'contents' , $contents );
	}

	/**
	 * インストールステップ２
	 */
	public function step2Action()
	{
		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'post_to',      "{$this->getCurrentUri(false)}/step3" );
		$this->view->assign( 'sitetitle',    'インストールステップ２' );
		$this->view->assign( 'message',      'システム設定を行います。このページの設定が正しく行われないとepgrecは機能しません。' );
		$this->view->assign( 'pdo_driver',   $this->model->getPdoDriverOptions() );
	}

	/**
	 * インストールステップ３
	 */
	public function step3Action()
	{
		// 設定の保存
		$POST_DATA = $this->request->getPost();
		if ($POST_DATA['token'] != '')
		{
			$this->setting->post($POST_DATA);
			$this->setting->save();
		}
		else
		{
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		// データベース接続チェック
		$this->model->initDb();
		if (!ModelBase::isConnect())
		{
			jdialog( 'ＤＢに接続できません。ホスト名/ユーザー名/パスワードを再チェックしてください。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		// DBテーブルの作成
		try
		{
			$rec = new DBRecord( RESERVE_TBL );
			$rec->createTable();

			$rec = new DBRecord( PROGRAM_TBL );
			$rec->createTable();

			$rec = new DBRecord( CHANNEL_TBL );
			$rec->createTable();

			$rec = new DBRecord( CATEGORY_TBL );
			$rec->createTable();

			$rec = new DBRecord( KEYWORD_TBL );
			$rec->createTable();

			$rec = new DBRecord( LOG_TBL );
			$rec->createTable();

			$rec = new DBRecord( USER_TBL );
			$rec->createTable();
		}
		catch ( Exception $e )
		{
			UtilLog::writeLog('テーブルの作成失敗: '.print_r($e, true));
			jdialog( 'テーブルの作成に失敗しました。データベースに権限がない等の理由が考えられます。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'sitetitle',    'インストールステップ３' );
		$this->view->assign( 'post_to',      "{$this->getCurrentUri(false)}/step4" );
		$this->view->assign( 'message' ,     '環境設定を行います。これらの設定はデフォルトのままでも制限付きながら動作します。' );
		$this->view->assign( 'record_mode' , $this->model->getRecModeOptions() );
	}

	/**
	 * インストールステップ４
	 */
	public function step4Action()
	{
		// 設定の保存
		$POST_DATA = $this->request->getPost();
		if ($POST_DATA['token'] != '')
		{
			$POST_DATA['is_installed'] = 1;
			$this->setting->post($POST_DATA);
			$this->setting->save();
		}
		else
		{
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		$this->view->assign( 'settings',  $this->setting );
		$this->view->assign( 'sitetitle', 'インストールステップ４' );
		$this->view->assign( 'post_to',   "{$this->getCurrentUri(false)}/step5" );
		$this->view->assign( 'message',   'ログイン設定を行います。このページの設定が正しく行われないとepgrecにログインできません。' );
		$this->view->assign( 'user_data' , array('name' => 'EpgRec管理者', 'login_name' => 'epgrec_admin', 'login_pass' => '') );
	}

	/**
	 * インストール最終ステップ
	 */
	public function step5Action()
	{
		// 設定の保存
		$POST_DATA = $this->request->getPost();
		if ($POST_DATA['token'] != '')
		{
			$rec = new DBRecord( USER_TBL );
			$rec->name = $POST_DATA['user_name'];
			$rec->level = 100;
			$rec->login_name = $POST_DATA['login_name'];
			$rec->login_pass = sha1($POST_DATA['login_pass']);
		}
		else
		{
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		$this->view->assign( 'settings',     $this->setting );
		$this->view->assign( 'install_path', INSTALL_PATH );
		$this->view->assign( 'sitetitle',    'インストール最終ステップ' );
	}

	/**
	 * インストール完了
	 */
	public function step6Action()
	{
		$POST_DATA = $this->request->getPost();
		if ($POST_DATA['token'] != '')
		{
			$proc = new EpgrecProc( GET_EPG_CMD.' &' );
			$proc->startCommand();
		}
		else
		{
			jdialog( '不正なアクセスです。', "{$this->getCurrentUri(false)}/step2" );
			exit;
		}

		$this->view->assign( 'settings',  $this->setting );
		$this->view->assign( 'sitetitle', 'インストール完了' );
	}
}
?>