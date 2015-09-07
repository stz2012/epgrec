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
		$err_flg = false;
		$contents = "<p><b>epgrecのインストール状態をチェックします</b></p>";

		// do-record.shの存在チェック
		if (! file_exists( DO_RECORD ) )
		{
			$err_flg = true;
			$contents .= "do-record.shが存在しません<br>do-record.sh.pt1やdo-record.sh.friioを参考に作成してください<br />";
		}

		// パーミッションチェック
		$rw_dirs = array( 
			INSTALL_PATH."/settings",
			INSTALL_PATH."/thumbs",
			INSTALL_PATH."/video",
			INSTALL_PATH."/views/templates_c",
		);

		$gen_thumbnail = INSTALL_PATH."/scripts/gen-thumbnail.sh";
		if ( defined("GEN_THUMBNAIL") )
			$gen_thumbnail = GEN_THUMBNAIL;

		$exec_files = array(
			DO_RECORD,
			RECORDER_CMD,
			INSTALL_PATH."/scripts/getepg.php",
			INSTALL_PATH."/scripts/storeProgram.php",
			$gen_thumbnail,
		);

		$contents .= "<br />";
		$contents .= "<p><b>ディレクトリのパーミッションチェック（777）</b></p>";
		$contents .= "<div>";
		foreach($rw_dirs as $value )
		{
			$contents .= $value;
			$perm = $this->_getPerm( $value );
			if ( !($perm == "707" || $perm == "777") )
			{
				$err_flg = true;
				$contents .= '<font color="red">...'.$perm.'... missing</font><br />このディレクトリを書き込み許可にしてください（ex. chmod 777 '.$value.'）<br />';
			}
			else
				$contents .= "...".$perm."...ok<br />";
		}
		$contents .= "</div>";

		$contents .= "<br />";
		$contents .= "<p><b>ファイルのパーミッションチェック（755）</b></p>";
		$contents .= "<div>";
		foreach($exec_files as $value )
		{
			$contents .= $value;
			$perm = $this->_getPerm( $value );
			if ( !($perm == "755" || $perm == "775" || $perm == "777") )
			{
				$err_flg = true;
				$contents .= '<font color="red">...'.$perm.'... missing</font><br>このファイルを実行可にしてください（ex. chmod 755 '.$value.'）<br />';
			}
			else
				$contents .= "...".$perm."...ok<br />";
		}
		$contents .= "</div>";

		$contents .= "<br />";
		$contents .= "<p><b>地上デジタルチャンネルの設定確認</b></p>";
		$contents .= "<div>現在、config.phpでは以下のチャンネルの受信が設定されています。受信不可能なチャンネルが混ざっていると番組表が表示できません。</div>";
		$contents .= "<ul>";
		foreach( $GR_CHANNEL_MAP as $key => $value )
		{
			$contents .= "<li>物理チャンネル".$value."</li>";
		}
		$contents .= "</ul>";

		if (!$err_flg)
			$contents .= "<p><a href=\"{$this->getCurrentUri(false)}/step2\">以上を確認し次の設定に進む</a></p>";

		$this->view->assign( "sitetitle", "インストールステップ１" );
		$this->view->assign( "contents" , $contents );
	}

	/**
	 * インストールステップ２
	 */
	public function step2Action()
	{
		global $RECORD_MODE;
		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "install_path", INSTALL_PATH );
		$this->view->assign( "post_to", "{$this->getCurrentUri(false)}/step3" );
		$this->view->assign( "sitetitle", "インストールステップ２" );
		$this->view->assign( "message", "システム設定を行います。このページの設定が正しく行われないとepgrecは機能しません。" );
		$this->view->assign( "record_mode", $RECORD_MODE );
	}

	/**
	 * インストールステップ３
	 */
	public function step3Action()
	{
		global $RECORD_MODE;
		// 設定の保存
		$this->setting->post($this->request->getPost());
		$this->setting->save();

		// データベース接続チェック
		$dbh = @mysql_connect( $this->setting->db_host, $this->setting->db_user, $this->setting->db_pass );
		if ( $dbh == false )
		{
			jdialog( "MySQLに接続できません。ホスト名/ユーザー名/パスワードを再チェックしてください", "{$this->getCurrentUri(false)}/step2" );
			exit();
		}

		$sqlstr = "use ".$this->setting->db_name;
		$res = @mysql_query( $sqlstr );
		if ( $res == false )
		{
			jdialog( "データベース名が異なるようです", "{$this->getCurrentUri(false)}/step2" );
			exit();
		}

		// DBテーブルの作成
		try
		{
			$rec = new DBRecord( RESERVE_TBL );
			$rec->createTable( RESERVE_STRUCT );

			$rec = new DBRecord( PROGRAM_TBL );
			$rec->createTable( PROGRAM_STRUCT );

			$rec = new DBRecord( CHANNEL_TBL );
			$rec->createTable( CHANNEL_STRUCT );

			$rec = new DBRecord( CATEGORY_TBL );
			$rec->createTable( CATEGORY_STRUCT );

			$rec = new DBRecord( KEYWORD_TBL );
			$rec->createTable( KEYWORD_STRUCT );

			$rec = new DBRecord( LOG_TBL );
			$rec->createTable( LOG_STRUCT );
		}
		catch( Exception $e )
		{
			jdialog("テーブルの作成に失敗しました。データベースに権限がない等の理由が考えられます。", "{$this->getCurrentUri(false)}/step2" );
			exit();
		}

		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "install_path", INSTALL_PATH );
		$this->view->assign( "sitetitle", "インストールステップ３" );
		$this->view->assign( "post_to", "{$this->getCurrentUri(false)}/step4" );
		$this->view->assign( "message" , "環境設定を行います。これらの設定はデフォルトのままでも制限付きながら動作します。" );
		$this->view->assign( "record_mode", $RECORD_MODE );
	}

	/**
	 * インストール最終ステップ
	 */
	public function step4Action()
	{
		// 設定の保存
		$this->setting->post($this->request->getPost());
		$this->setting->save();

		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "install_path", INSTALL_PATH );
		$this->view->assign( "sitetitle", "インストール最終ステップ" );
	}

	/**
	 * インストール完了
	 */
	public function step5Action()
	{
		@system( INSTALL_PATH.'/scripts/getepg.php &' );
		$this->view->assign( "settings", $this->setting );
		$this->view->assign( "sitetitle", "インストール完了" );
	}

	// パーミッションを返す
	private function _getPerm( $file )
	{
		$ss = @stat( $file );
		return sprintf("%o", ($ss['mode'] & 000777));
	}
}
?>