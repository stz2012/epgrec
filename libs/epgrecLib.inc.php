<?php
// ライブラリ

define( "EPGREC_INFO" , 0 );
define( "EPGREC_WARN" , 1 );
define( "EPGREC_ERROR", 2 );

$PDO_DRIVER_MAP = array(
	'mysql'  => 'MySQL',
	'pgsql'  => 'PostgreSQL',
	'sqlite' => 'SQLite'
);

function reclog( $message , $level = EPGREC_INFO )
{
	try
	{
		$log = new DBRecord( LOG_TBL );
		$log->logtime = date("Y-m-d H:i:s");
		$log->level = $level;
		$log->message = $message;
	}
	catch ( Exception $e )
	{
		UtilLog::writeLog("ログ出力失敗: ".print_r($e, true));
	}
}

function toTimestamp( $param )
{
	sscanf( $param, "%4d-%2d-%2d %2d:%2d:%2d", $yyyy, $mm, $dd, $hh, $ii, $ss );
	return mktime( $hh, $ii, $ss, $mm, $dd, $yyyy );
}

function toDatetime( $timestamp )
{
	return date("Y-m-d H:i:s", $timestamp);
}

function toDatetime2( $param )
{
	$param = str_replace(' +0900', '', $param);
	sscanf( $param, "%4d%2d%2d%2d%2d%2d", $yyyy, $mm, $dd, $hh, $ii, $ss );
	return toDatetime( mktime( $hh, $ii, $ss, $mm, $dd, $yyyy ) );
}

function jdialog( $message, $url = "index.php" )
{
    header( "Content-Type: text/html;charset=utf-8" );
    exit( "<script type=\"text/javascript\">\n" .
          "<!--\n".
         "alert(\"". $message . "\");\n".
         "window.open(\"".$url."\",\"_self\");".
         "// -->\n</script>" );
}

/**
 * クラスのオートロード
 * @param string $className クラス名
 */
function custom_autoloader($className)
{
	$file_name = preg_replace('/[^a-z_A-Z0-9]/u', '', $className) . '.php';
	require_once $file_name;
}

function check_epgdump_file( $file )
{
	// ファイルがないなら無問題
	if ( ! file_exists( $file ) ) return true;

	// 1時間以上前のファイルなら削除してやり直す
	if ( (time() - filemtime( $file )) > 3600 )
	{
		@unlink( $file );
		return true;
	}

	return false;
}

function parse_epgdump_file( $type, $xmlfile )
{
	$settings = Settings::factory();
	$ch_map = array();

	// XML parse
	$xml = @simplexml_load_file( $xmlfile );
	if ( $xml === false )
	{
		reclog( "parse_epgdump_file:: 正常な".$xmlfile."が作成されなかった模様(放送間帯でないなら問題ありません)", EPGREC_WARN );
		return;	// XMLが読み取れないなら何もしない
	}

	// channel抽出
	foreach ( $xml->channel as $ch )
	{
		$ch_name = (string)$ch->{'display-name'};
		$ch_disc = (string)$ch['id'];
		$ch_map["$ch_disc"] = (string)$ch['tp'];
		$tmp_arr = explode('_', $ch_disc);
		$sid = $tmp_arr[1];
		try
		{
			// チャンネルデータを探す
			$num = DBRecord::countRecords( CHANNEL_TBL , "WHERE channel_disc = '{$ch_disc}'" );
			if ( $num == 0 )
			{
				// チャンネルデータがないなら新規作成
				$rec = new DBRecord( CHANNEL_TBL );
				$rec->type = $type;
				$rec->name = $ch_name;
				$rec->channel = $ch_map["$ch_disc"];
				$rec->channel_disc = $ch_disc;
				$rec->sid = $sid;
				reclog("parse_epgdump_file:: 新規チャンネル {$ch_name} を追加" );
			}
			else
			{
				// 存在した場合も、とりあえずチャンネル名は更新する
				$rec = new DBRecord(CHANNEL_TBL, "channel_disc", $ch_disc );
				$rec->name = $ch_name;
				// BS／CSの場合、チャンネル番号とSIDを更新
				if ( $type == "BS" ||  $type == "CS" )
				{
					$rec->channel = $ch_map["$ch_disc"];
					$rec->sid = $sid;
				}
			}
		}
		catch ( Exception $e )
		{
			reclog( "parse_epgdump_file::DBの接続またはチャンネルテーブルの書き込みに失敗", EPGREC_ERROR );
			reclog( "parse_epgdump_file:: ".$e->getMessage()."" , EPGREC_ERROR );
			exit( $e->getMessage() );
		}
	}
	// channel 終了

	// programme 取得
	foreach ( $xml->programme as $program )
	{
		$channel_rec = null;
		$channel_disc = (string)$program['channel']; 
		if ( ! array_key_exists( "$channel_disc", $ch_map ) ) continue;
		$channel = $ch_map["$channel_disc"];

		try
		{
			$channel_rec = new DBRecord(CHANNEL_TBL, "channel_disc", "$channel_disc" );
		}
		catch ( Exception $e )
		{
			reclog( "parse_epgdump_file::チャンネルレコード {$channel_disc} が発見できない", EPGREC_ERROR );
		}
		if ( $channel_rec == null ) continue;	// あり得ないことが起きた
		if ( $channel_rec->skip == 1 ) continue;	// 受信しないチャンネル

		$starttime = toDatetime2( (string)$program['start'] );
		$endtime = toDatetime2( (string)$program['stop'] );
		$title = (string)$program->title;
		$desc = (string)$program->desc;
		$cat_ja = "";
		$cat_en = "";
		foreach ( $program->category as $cat )
		{
			if ( (string)$cat['lang'] == "ja_JP" ) $cat_ja = (string)$cat;
			if ( (string)$cat['lang'] == "en" ) $cat_en = (string)$cat;
		}
		$program_disc = md5( $channel_disc . $starttime . $endtime );
		// printf( "%s %s %s %s %s %s %s \n", $program_disc, $channel, $starttime, $endtime, $title, $desc, $cat_ja );

		// カテゴリ登録
		$cat_rec = null;
		try
		{
			// カテゴリを処理する
			$category_disc = md5( $cat_ja . $cat_en );
			$num = DBRecord::countRecords(CATEGORY_TBL, "WHERE category_disc = '{$category_disc}'" );
			if ( $num == 0 )
			{
				// 新規カテゴリの追加
				$cat_rec = new DBRecord( CATEGORY_TBL );
				$cat_rec->name_jp = $cat_ja;
				$cat_rec->name_en = $cat_en;
				$cat_rec->category_disc = $category_disc;
				reclog("parse_epgdump_file:: 新規カテゴリ {$cat_ja} を追加" );
			}
			else
				$cat_rec = new DBRecord(CATEGORY_TBL, "category_disc" , $category_disc );
		}
		catch ( Exception $e )
		{
			reclog("parse_epgdump_file:: カテゴリテーブルのアクセスに失敗した模様", EPGREC_ERROR );
			reclog("parse_epgdump_file:: ".$e->getMessage()."" , EPGREC_ERROR );
			exit( $e->getMessage() );
		}

		// プログラム登録
		try
		{
			//
			$num = DBRecord::countRecords(PROGRAM_TBL, "WHERE program_disc = '{$program_disc}'" );
			if ( $num == 0 )
			{
				// 新規番組
				// 重複チェック 同時間帯にある番組
				$options = "WHERE channel_disc = '{$channel_disc}'";
				$options .= " AND starttime < CAST('{$endtime}' AS TIMESTAMP)";
				$options .= " AND endtime > CAST('{$starttime}' AS TIMESTAMP)";
				$battings = DBRecord::countRecords(PROGRAM_TBL, $options );
				if ( $battings > 0 )
				{
					// 重複発生＝おそらく放映時間の変更
					$records = DBRecord::createRecords(PROGRAM_TBL, $options);
					foreach ( $records as $rec )
					{
						// 自動録画予約された番組は放映時間変更と同時にいったん削除する
						try
						{
							$reserve = new DBRecord(RESERVE_TBL, "program_id", $rec->id );
							// すでに開始されている録画は無視する
							if ( time() > (toTimestamp($reserve->starttime) - PADDING_TIME - $settings->former_time) )
							{
								reclog( "parse_epgdump_file::録画ID".$reserve->id.":".$reserve->type.$reserve->channel.$reserve->title."は録画開始後に時間変更が発生した可能性がある", EPGREC_WARN );
							}
							else
							{
								if ( $reserve->autorec )
								{
									reclog( "parse_epgdump_file::録画ID".$reserve->id.":".$reserve->type.$reserve->channel.$reserve->title."は時間変更の可能性があり予約取り消し" );
									Reservation::cancel( $reserve->id );
								}
							}
						}
						catch ( Exception $e ) {
							// 無視
						}
						// 番組削除
						reclog( "parse_epgdump_file::放送時間重複が発生した番組ID".$rec->id." ".$rec->type.$rec->channel.$rec->title."を削除" );
						$rec->delete();
					}
				}
				// 番組内容登録
				$rec = new DBRecord( PROGRAM_TBL );
				$rec->channel_disc = $channel_disc;
				$rec->channel_id = $channel_rec->id;
				$rec->type = $type;
				$rec->channel = $channel_rec->channel;
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				$rec->starttime = $starttime;
				$rec->endtime = $endtime;
				$rec->program_disc = $program_disc;
			}
			else
			{
				// 番組内容更新
				$rec = new DBRecord( PROGRAM_TBL, "program_disc", $program_disc );
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				try
				{
					$reserve = new DBRecord( RESERVE_TBL, "program_id", $rec->id );
					// dirtyが立っておらず現在より後の録画予約であるなら
					if ( ($reserve->dirty == 0) && (toTimestamp($reserve->starttime) > time()) )
					{
						$reserve->title = $title;
						$reserve->description = $desc;
						reclog( "parse_epgdump_file:: 予約ID".$reserve->id."のEPG情報が更新された" );
					}
				}
				catch ( Exception $e ) {
					// 無視する
				}
				// 書き込む
			}
		}
		catch (Exception $e)
		{
			reclog( "parse_epgdump_file:: プログラムテーブルに問題が生じた模様", EPGREC_ERROR );
			reclog( "parse_epgdump_file:: ".$e->getMessage()."" , EPGREC_ERROR);
			exit( $e->getMessage() );
		}
	}
	// Programme取得完了
}

// 不要なプログラムの削除
function garbageClean()
{
	$settings = Settings::factory();
	// 8日以上前のプログラムを消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE endtime < (now() - INTERVAL '8 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE endtime < datetime('now', '-8 days')" );
	else
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE endtime < (now() - INTERVAL 8 DAY)" );

	// 8日以上先のデータがあれば消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE starttime > (now() + INTERVAL '8 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE starttime > datetime('now', '+8 days')" );
	else
		DBRecord::deleteRecords( PROGRAM_TBL, "WHERE starttime > (now() + INTERVAL 8 DAY)" );

	// 10日以上前のログを消す
	if ($settings->db_type == 'pgsql')
		DBRecord::deleteRecords( LOG_TBL, "WHERE logtime < (now() - INTERVAL '10 DAY')" );
	else if ($settings->db_type == 'sqlite')
		DBRecord::deleteRecords( LOG_TBL, "WHERE logtime < datetime('now', '-10 days')" );
	else
		DBRecord::deleteRecords( LOG_TBL, "WHERE logtime < (now() - INTERVAL 10 DAY)" );
}

// キーワード自動録画予約
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
		catch ( Exception $e ) {
			// 無視
		}
	}
}

// 省電力
function doPowerReduce($isGetEpg = false)
{
	$settings = Settings::factory();
	if ( intval($settings->use_power_reduce) != 0 )
	{
		if ( file_exists(INSTALL_PATH. "/settings/wakeupvars.xml") )
		{
			$wakeupvars_text = file_get_contents( INSTALL_PATH. "/settings/wakeupvars.xml" );
			$wakeupvars = new SimpleXMLElement($wakeupvars_text);
			if (DBRecord::getDbType() == 'pgsql')
				$options = "WHERE complete <> '1' AND starttime < (now() + INTERVAL '1 DAY') AND endtime > now()";
			else if (DBRecord::getDbType() == 'sqlite')
				$options = "WHERE complete <> '1' AND starttime < datetime('now', '+1 days') AND endtime > datetime('now')";
			else
				$options = "WHERE complete <> '1' AND starttime < (now() + INTERVAL 1 DAY) AND endtime > now()";

			// 起動理由を調べる
			if ( strcasecmp( "getepg", $wakeupvars->reason ) == 0 )
			{
				// 1時間以内に録画はないか？
				$num = DBRecord::countRecords( RESERVE_TBL, $options );
				if ( $num != 0 )
				{	// 録画があるなら録画起動にして終了
					$wakeupvars->reason = "reserve";
				}
				else
				{
					exec( $settings->shutdown . " -h +".$settings->wakeup_before );
				}
			}
			else if ( strcasecmp( "reserve", $wakeupvars->reason ) == 0 )
			{
				// 1時間以内に録画はないか？
				$num = DBRecord::countRecords( RESERVE_TBL, $options );
				if ( $num != 0 )
				{	// 録画があるなら何もしない
					exit();
				}
				exec( $settings->shutdown . " -h +".$settings->wakeup_before );
			}

			// getepg終了時を書込み
			if ($isGetEpg)
			{
				$wakeupvars->getepg_time = time();
				$wakeupvars->asXML(INSTALL_PATH. "/settings/wakeupvars.xml");
			}
		}
	}
}

function filesize_n($path)
{
	$size = @filesize($path);
	if ( $size <= 0 )
	{
		ob_start();
		system('ls -al "'.$path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
		$size = ob_get_clean();
	}
	return human_filesize($size);
}

function human_filesize($bytes, $decimals = 2)
{
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
?>