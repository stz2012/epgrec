<?php
/**
 * Epgrecライブラリ
 */

/**
 * 日時文字列 → タイムスタンプ変換
 * @param string $param 日時文字列
 * @return int タイムスタンプ
 */
function toTimestamp( $param )
{
	sscanf( $param, '%4d-%2d-%2d %2d:%2d:%2d', $yyyy, $mm, $dd, $hh, $ii, $ss );
	return mktime( $hh, $ii, $ss, $mm, $dd, $yyyy );
}

/**
 * タイムスタンプ → 日時文字列変換
 * @param int $param タイムスタンプ
 * @return string 日時文字列
 */
function toDatetime( $timestamp )
{
	return date('Y-m-d H:i:s', $timestamp);
}

/**
 * EPG日時情報 → 日時文字列変換
 * @param string $param EPG日時情報（YYYYMMDDHHIISS +0900）
 * @return string 日時文字列
 */
function toDatetime2( $param )
{
	$param = str_replace(' +0900', '', $param);
	sscanf( $param, '%4d%2d%2d%2d%2d%2d', $yyyy, $mm, $dd, $hh, $ii, $ss );
	return toDatetime( mktime( $hh, $ii, $ss, $mm, $dd, $yyyy ) );
}

/**
 * JSダイアログ表示
 * @param string $message メッセージ
 * @param string $url     転送先URL
 */
function jdialog( $message, $url = BASE_URI )
{
	header( 'Content-Type: text/html;charset=utf-8' );
	exit( "<script type=\"text/javascript\">\n".
	      "<!--\n".
	      "alert(\"". $message . "\");\n".
	      "window.open(\"".$url."\", \"_self\");\n".
	      "// -->\n".
	      "</script>" );
}

/**
 * インストール環境チェック
 * @param string $contents 出力情報
 */
function check_epgrec_env( &$contents = '' )
{
	$err_flg = false;

	// 設定ファイルの状態チェック
	$settings = Settings::factory();
	if ( $settings->is_installed != 0 )
		return true;

	// do-record.shの存在チェック
	if ( ! file_exists( DO_RECORD ) )
	{
		$contents .= DO_RECORD.'が存在しません<br />do-record.sh.pt1やdo-record.sh.friioを参考に作成してください<br />';
		return false;
	}

	// パーミッションチェック
	$rw_dirs = array( 
		INSTALL_PATH.'/settings',
		INSTALL_PATH.'/htdocs/epgrec/thumbs',
		INSTALL_PATH.'/video',
		INSTALL_PATH.'/views/templates_c',
	);
	$exec_files = array(
		DO_RECORD,
		GEN_THUMBNAIL,
		GET_EPG_CMD,
		STORE_PRG_CMD,
		RECORDER_CMD,
		COMPLETE_CMD
	);

	$contents .= '<br />';
	$contents .= '<p><b>ディレクトリのパーミッションチェック（707）</b></p>';
	$contents .= '<div>';
	foreach ($rw_dirs as $value )
	{
		$contents .= $value;
		$perm = check_permission( $value );
		if ( !($perm == '707' || $perm == '777') )
		{
			$err_flg = true;
			$contents .= '<font color="red">...'.$perm.'... missing</font><br />このディレクトリを書き込み許可にしてください（ex. chmod 707 '.$value.'）<br />';
		}
		else
			$contents .= '...'.$perm.'...ok<br />';
	}
	$contents .= '</div>';

	$contents .= '<br />';
	$contents .= '<p><b>ファイルのパーミッションチェック（705）</b></p>';
	$contents .= '<div>';
	foreach ($exec_files as $value )
	{
		$contents .= $value;
		$perm = check_permission( $value );
		if ( !($perm == '705' || $perm == '755') )
		{
			$err_flg = true;
			$contents .= '<font color="red">...'.$perm.'... missing</font><br>このファイルを実行可にしてください（ex. chmod 705 '.$value.'）<br />';
		}
		else
			$contents .= '...'.$perm.'...ok<br />';
	}
	$contents .= '</div>';

	return ( $err_flg == false );
}

/**
 * パーミッション情報取得
 * @param string $path パス
 * @return string パーミッション
 */
function check_permission( $path )
{
	$ss = @stat( $path );
	return sprintf('%o', ($ss['mode'] & 000777));
}

/**
 * ACPIタイマー設定
 * @param string $wake_datetime 起動時間文字列
 */
function set_wakealarm( $wake_datetime )
{
	// ACPIタイマーパス（環境によっては要変更）
	$ACPI_TIMER_PATH = '/sys/class/rtc/rtc0/wakealarm';

	// いったんリセットする
	$fp = fopen( $ACPI_TIMER_PATH, 'w' );
	fwrite($fp , '0');
	fclose($fp);

	// 起動時間を書込（LOCAL／UTC時間）
	$fp = fopen( $ACPI_TIMER_PATH, 'w' );
	exec('cat /etc/adjtime | tail -n 1', $stdout);
	if (count($stdout) > 0 && $stdout[0] == 'LOCAL')
		fwrite($fp , ''.toTimestamp($wake_datetime));
	else
		fwrite($fp , ''.(toTimestamp($wake_datetime) - 60 * 60 * 9));
	fclose($fp);
}

/**
 * Epgdump変換データのチェック
 * @param string $xmlfile XMLファイルパス
 * @return bool 
 */
function check_epgdump_file( $xmlfile )
{
	// ファイルがないなら無問題
	if ( ! file_exists( $xmlfile ) ) return true;

	// 1時間以上前のファイルなら削除してやり直す
	if ( (time() - filemtime( $xmlfile )) > 3600 )
	{
		@unlink( $xmlfile );
		return true;
	}

	return false;
}

/**
 * Epgdump変換データの解析
 * @param string $type    チューナー種別
 * @param string $xmlfile XMLファイルパス
 */
function parse_epgdump_file( $type, $xmlfile )
{
	$settings = Settings::factory();
	$ch_map = array();

	// チューナー種別チェック
	switch ( $type )
	{
		case 'GR':
		case 'BS':
		case 'CS':
			break;
		case 'CS1':
		case 'CS2':
			$type = 'CS';
			break;
		default:
			UtilLog::outLog( "parse_epgdump_file:: 不正なチューナー種別です", UtilLog::LV_ERROR );
			return;
	}

	// XML parse
	$xml = @simplexml_load_file( $xmlfile );
	if ( $xml === false )
	{
		UtilLog::outLog( "parse_epgdump_file:: 正常なXMLファイル {$xmlfile} が作成されなかった模様(放送間帯でないなら問題ありません)", UtilLog::LV_WARN );
		return;	// XMLが読み取れないなら何もしない
	}

	// channel抽出
	foreach ( $xml->channel as $ch )
	{
		$ch_disc = (string)$ch['id'];
		list(, $ch_sid) = explode('_', $ch_disc);
		$ch_map["$ch_disc"] = array(
			'id'      => 0,
			'channel' => (string)$ch['tp'],
			'name'    => (string)$ch->{'display-name'},
			'sid'     => $ch_sid,
			'skip'    => 0
		);
		try
		{
			// チャンネルデータを探す
			$num = DBRecord::countRecords( CHANNEL_TBL , "WHERE channel_disc = '{$ch_disc}'" );
			if ( $num == 0 )
			{
				// チャンネルデータがないなら新規作成
				$rec = new DBRecord( CHANNEL_TBL );
				$rec->type = $type;
				$rec->name = $ch_map["$ch_disc"]['name'];
				$rec->channel = $ch_map["$ch_disc"]['channel'];
				$rec->channel_disc = $ch_disc;
				$rec->sid = $ch_map["$ch_disc"]['sid'];
				$rec->update();
				$ch_map["$ch_disc"]['id'] = $rec->id;
				UtilLog::outLog( "parse_epgdump_file:: 新規チャンネル {$rec->name} を追加" );
			}
			else
			{
				// 存在した場合も、とりあえずチャンネル名は更新する
				$rec = new DBRecord( CHANNEL_TBL, 'channel_disc', $ch_disc );
				$rec->name = $ch_map["$ch_disc"]['name'];
				// BS／CSの場合、チャンネル番号とSIDを更新
				if ( $type == 'BS' ||  $type == 'CS' )
				{
					$rec->channel = $ch_map["$ch_disc"]['channel'];
					$rec->sid = $ch_map["$ch_disc"]['sid'];
				}
				$rec->update();
				$ch_map["$ch_disc"]['id'] = $rec->id;
				$ch_map["$ch_disc"]['skip'] = $rec->skip;
			}
		}
		catch ( Exception $e )
		{
			UtilLog::outLog( 'parse_epgdump_file:: DBの接続またはチャンネルテーブルの書き込みに失敗', UtilLog::LV_ERROR );
			throw $e;
		}
	}
	// channel 終了

	// programme 取得
	foreach ( $xml->programme as $program )
	{
		$channel_disc = (string)$program['channel']; 
		if ( ! array_key_exists( "$channel_disc", $ch_map ) )
		{
			UtilLog::outLog( "parse_epgdump_file:: チャンネルレコード {$channel_disc} が発見できない", UtilLog::LV_ERROR );
			continue;
		}
		if ( $ch_map["$channel_disc"]['skip'] == 1 )
			continue;	// 受信しないチャンネル

		$starttime = toDatetime2( (string)$program['start'] );
		$endtime = toDatetime2( (string)$program['stop'] );
		$title = (string)$program->title;
		$desc = (string)$program->desc;
		$cat_ja = '';
		$cat_en = '';
		foreach ( $program->category as $cat )
		{
			if ( (string)$cat['lang'] == 'ja_JP' ) $cat_ja = (string)$cat;
			if ( (string)$cat['lang'] == 'en' ) $cat_en = (string)$cat;
		}
		$program_disc = md5( $channel_disc . $starttime . $endtime );

		// カテゴリ登録
		$cat_rec = null;
		try
		{
			// カテゴリを処理する
			$category_disc = md5( $cat_ja . $cat_en );
			$num = DBRecord::countRecords( CATEGORY_TBL, "WHERE category_disc = '{$category_disc}'" );
			if ( $num == 0 )
			{
				// 新規カテゴリの追加
				$cat_rec = new DBRecord( CATEGORY_TBL );
				$cat_rec->name_jp = $cat_ja;
				$cat_rec->name_en = $cat_en;
				$cat_rec->category_disc = $category_disc;
				$cat_rec->update();
				UtilLog::outLog("parse_epgdump_file:: 新規カテゴリ {$cat_rec->name_jp} を追加" );
			}
			else
				$cat_rec = new DBRecord( CATEGORY_TBL, 'category_disc' , $category_disc );
		}
		catch ( Exception $e )
		{
			UtilLog::outLog( 'parse_epgdump_file:: カテゴリテーブルのアクセスに失敗した模様', UtilLog::LV_ERROR );
			throw $e;
		}

		// プログラム登録
		try
		{
			//
			$num = DBRecord::countRecords( PROGRAM_TBL, "WHERE program_disc = '{$program_disc}'" );
			if ( $num == 0 )
			{	// 新規番組
				// 重複チェック 同時間帯にある番組
				$options = "WHERE channel_disc = '{$channel_disc}'";
				if ($settings->db_type == 'pgsql')
				{
					$options .= " AND starttime < CAST('{$endtime}' AS TIMESTAMP)";
					$options .= " AND endtime > CAST('{$starttime}' AS TIMESTAMP)";
				}
				else if ($settings->db_type == 'sqlite')
				{
					$options .= " AND datetime(starttime) < datetime('{$endtime}')";
					$options .= " AND datetime(endtime) > datetime('{$starttime}')";
				}
				else
				{
					$options .= " AND starttime < CAST('{$endtime}' AS DATETIME)";
					$options .= " AND endtime > CAST('{$starttime}' AS DATETIME)";
				}
				$battings = DBRecord::countRecords( PROGRAM_TBL, $options );
				if ( $battings > 0 )
				{
					// 重複発生＝おそらく放映時間の変更
					$records = DBRecord::createRecords( PROGRAM_TBL, $options );
					foreach ( $records as $rec )
					{
						// 自動録画予約された番組は放映時間変更と同時にいったん削除する
						try
						{
							$reserve = new DBRecord( RESERVE_TBL, 'program_id', $rec->id );
							// すでに開始されている録画は無視する
							if ( time() > (toTimestamp($reserve->starttime) - PADDING_TIME - $settings->former_time) )
							{
								UtilLog::outLog( "parse_epgdump_file:: 録画ID：{$reserve->id} {$reserve->channel} {$reserve->title} は録画開始後に時間変更が発生した可能性がある", UtilLog::LV_WARN );
							}
							else
							{
								if ( $reserve->autorec )
								{
									UtilLog::outLog( "parse_epgdump_file:: 録画ID：{$reserve->id} {$reserve->channel} {$reserve->title} は時間変更の可能性があり予約取り消し" );
									Reservation::cancel( $reserve->id );
								}
							}
						}
						catch ( Exception $e )
						{
							// 無視
						}
						// 番組削除
						UtilLog::outLog( "parse_epgdump_file:: 放送時間重複が発生した番組ID：{$rec->id} {$rec->channel} {$rec->title} を削除" );
						$rec->delete();
					}
				}

				// 番組内容登録
				$rec = new DBRecord( PROGRAM_TBL );
				$rec->channel_disc = $channel_disc;
				$rec->channel_id = $ch_map["$channel_disc"]['id'];
				$rec->type = $type;
				$rec->channel = $ch_map["$channel_disc"]['channel'];
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				$rec->starttime = $starttime;
				$rec->endtime = $endtime;
				$rec->program_disc = $program_disc;
				$rec->update();
			}
			else
			{
				// 番組内容更新
				$rec = new DBRecord( PROGRAM_TBL, 'program_disc', $program_disc );
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				$rec->update();
				try
				{
					$reserve = new DBRecord( RESERVE_TBL, 'program_id', $rec->id );
					// dirtyが立っておらず現在より後の録画予約であるなら
					if ( ($reserve->dirty == 0) && (toTimestamp($reserve->starttime) > time()) )
					{
						$reserve->title = $title;
						$reserve->description = $desc;
						$reserve->update();
						UtilLog::outLog( "parse_epgdump_file:: 予約ID：{$reserve->id} {$reserve->channel} {$reserve->title} のEPG情報が更新された" );
					}
				}
				catch ( Exception $e )
				{
					// 無視する
				}
				// 書き込む
			}
		}
		catch ( Exception $e )
		{
			UtilLog::outLog( 'parse_epgdump_file:: プログラムテーブルに問題が生じた模様', UtilLog::LV_ERROR );
			throw $e;
		}
	}
	// Programme取得完了
}

/**
 * 不要なプログラムの削除
 */
function garbageClean()
{
	$settings = Settings::factory();
	// 8日以上前のプログラムを消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE endtime < (now() - INTERVAL '8 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE datetime(endtime) < datetime('now', '-8 days', 'localtime')" );
	else
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE endtime < (now() - INTERVAL 8 DAY)" );

	// 8日以上先のデータがあれば消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE starttime > (now() + INTERVAL '8 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE datetime(starttime) > datetime('now', '+8 days', 'localtime')" );
	else
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE starttime > (now() + INTERVAL 8 DAY)" );

	// 10日以上前のログを消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( LOG_TBL, "WHERE logtime < (now() - INTERVAL '10 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( LOG_TBL, "WHERE datetime(logtime) < datetime('now', '-10 days', 'localtime')" );
	else
		DBRecord::deleteRecords( LOG_TBL, "WHERE logtime < (now() - INTERVAL 10 DAY)" );
	UtilSQLite::cleanEventLog();
}

/**
 * キーワード自動録画予約
 */
function doKeywordReservation()
{
 	$recs = array();
	$recs = DBRecord::createRecords( KEYWORD_TBL );
	foreach ( $recs as $rec )
	{
		try
		{
			Reservation::keyword( $rec->id );
		}
		catch ( Exception $e )
		{
			// 無視
		}
	}
}
?>