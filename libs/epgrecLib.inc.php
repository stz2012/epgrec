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
	
	try {
		$log = new DBRecord( LOG_TBL );
		
		$log->logtime = date("Y-m-d H:i:s");
		$log->level = $level;
		$log->message = $message;
	}
	catch( Exception $e ) {
		// 
	}
}

function toTimestamp( $string )
{
	sscanf( $string, "%4d-%2d-%2d %2d:%2d:%2d", $y, $mon, $day, $h, $min, $s );
	return mktime( $h, $min, $s, $mon, $day, $y );
}

function toDatetime( $timestamp )
{
	return date("Y-m-d H:i:s", $timestamp);
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
	$map = array();

	// XML parse
	$xml = @simplexml_load_file( $xmlfile );
	if ( $xml === false )
	{
		reclog( "parse_epgdump_file:: 正常な".$xmlfile."が作成されなかった模様(放送間帯でないなら問題ありません)", EPGREC_WARN );
		return;	// XMLが読み取れないなら何もしない
	}

	// channel抽出
	foreach( $xml->channel as $ch )
	{
		$disc = $ch['id'];
		$tmp_arr = explode('_', $ch['id']);
		$sid = $tmp_arr[1];
		$map["$disc"] = $ch['tp'];
		try
		{
			// チャンネルデータを探す
			$num = DBRecord::countRecords( CHANNEL_TBL , "WHERE channel_disc = '" . $disc ."'" );
			if ( $num == 0 )
			{
				// チャンネルデータがないなら新規作成
				$rec = new DBRecord( CHANNEL_TBL );
				$rec->type = $type;
				$rec->channel = $map["$disc"];
				$rec->channel_disc = $disc;
				$rec->name = $ch->{'display-name'};
				$rec->sid = $sid;
				$rec->update();
			}
			else
			{
				// 存在した場合も、とりあえずチャンネル名は更新する
				$rec = new DBRecord(CHANNEL_TBL, "channel_disc", $disc );
				$rec->name = $ch->{'display-name'};
				// BS／CSの場合、チャンネル番号とSIDを更新
				if ( $type == "BS" ||  $type == "CS" )
				{
					$rec->channel = $map["$disc"];
					$rec->sid = $sid;
				}
				$rec->update();
			}
		}
		catch( Exception $e ) {
			reclog( "parse_epgdump_file::DBの接続またはチャンネルテーブルの書き込みに失敗", EPGREC_ERROR );
		}
	}
	// channel 終了

	// programme 取得
	foreach( $xml->programme as $program )
	{
		$channel_rec = null;
		$channel_disc = $program['channel']; 
		if ( ! array_key_exists( "$channel_disc", $map ) ) continue;
		$channel = $map["$channel_disc"];
		
		try {
			$channel_rec = new DBRecord(CHANNEL_TBL, "channel_disc", "$channel_disc" );
		}
		catch( Exception $e ) {
			reclog( "parse_epgdump_file::チャンネルレコード $channel_disc が発見できない", EPGREC_ERROR );
		}
		if ( $channel_rec == null ) continue;	// あり得ないことが起きた
		if ( $channel_rec->skip == 1 ) continue;	// 受信しないチャンネル
		
		$starttime = str_replace(" +0900", '', $program['start'] );
		$endtime = str_replace( " +0900", '', $program['stop'] );
		$title = $program->title;
		$desc = $program->desc;
		$cat_ja = "";
		$cat_en = "";
		foreach( $program->category as $cat )
		{
			if ( $cat['lang'] == "ja_JP" ) $cat_ja = $cat;
			if ( $cat['lang'] == "en" ) $cat_en = $cat;
		}
		$program_disc = md5( $channel_disc . $starttime . $endtime );
		// printf( "%s %s %s %s %s %s %s \n", $program_disc, $channel, $starttime, $endtime, $title, $desc, $cat_ja );

		// カテゴリ登録
		$cat_rec = null;
		try
		{
			// カテゴリを処理する
			$category_disc = md5( $cat_ja . $cat_en );
			$num = DBRecord::countRecords(CATEGORY_TBL, "WHERE category_disc = '".$category_disc."'" );
			if ( $num == 0 )
			{
				// 新規カテゴリの追加
				$cat_rec = new DBRecord( CATEGORY_TBL );
				$cat_rec->name_jp = $cat_ja;
				$cat_rec->name_en = $cat_en;
				$cat_rec->category_disc = $category_disc;
				reclog("parse_epgdump_file:: 新規カテゴリ".$cat_ja."を追加" );
			}
			else
				$cat_rec = new DBRecord(CATEGORY_TBL, "category_disc" , $category_disc );
		}
		catch( Exception $e ) {
			reclog("parse_epgdump_file:: カテゴリテーブルのアクセスに失敗した模様", EPGREC_ERROR );
			reclog("parse_epgdump_file:: ".$e->getMessage()."" ,EPGREC_ERROR );
			exit( $e->getMessage() );
		}

		// プログラム登録
		try
		{
			//
			$num = DBRecord::countRecords(PROGRAM_TBL, "WHERE program_disc = '".$program_disc."'" );
			if ( $num == 0 )
			{
				// 新規番組
				// 重複チェック 同時間帯にある番組
				$options = "WHERE channel_disc = '".$channel_disc."' ".
					"AND starttime < '". $endtime ."' AND endtime > '".$starttime."'";
				$battings = DBRecord::countRecords(PROGRAM_TBL, $options );
				if ( $battings > 0 )
				{
					// 重複発生＝おそらく放映時間の変更
					$records = DBRecord::createRecords(PROGRAM_TBL, $options);
					foreach( $records as $rec )
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
						catch( Exception $e ) {
							// 無視
						}
						// 番組削除
						reclog( "parse_epgdump_file::放送時間重複が発生した番組ID".$rec->id." ".$rec->type.$rec->channel.$rec->title."を削除" );
						$rec->delete();
					}
				}
				// //
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
				$rec->update();
			}
			else
			{
				// 番組内容更新
				$rec = new DBRecord( PROGRAM_TBL, "program_disc", $program_disc );
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				$rec->update();
				try
				{
					$reserve = new DBRecord( RESERVE_TBL, "program_id", $rec->id );
					// dirtyが立っておらず現在より後の録画予約であるなら
					if ( ($reserve->dirty == 0) && (toTimestamp($reserve->starttime) > time()) )
					{
						$reserve->title = $title;
						$reserve->description = $desc;
						reclog( "parse_epgdump_file:: 予約ID".$reserve->id."のEPG情報が更新された" );
						$reserve->update();
					}
				}
				catch( Exception $e ) {
					// 無視する
				}
				// 書き込む
			}
		}
		catch(Exception $e)
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
	// 8日以上前のプログラムを消す
	$arr = array();
	$arr = DBRecord::createRecords(  PROGRAM_TBL, "WHERE endtime < subdate( now(), 8 )" );
	foreach( $arr as $val ) $val->delete();
	
	// 8日以上先のデータがあれば消す
	$arr = array();
	$arr = DBRecord::createRecords(  PROGRAM_TBL, "WHERE starttime  > adddate( now(), 8 ) ");
	foreach( $arr as $val ) $val->delete();

	// 10日以上前のログを消す
	$arr = array();
	$arr = DBRecord::createRecords(  LOG_TBL, "WHERE logtime < subdate( now(), 10 )" );
	foreach( $arr as $val ) $val->delete();
}

// キーワード自動録画予約
function doKeywordReservation()
{
 	$arr = array();
	$arr = Keyword::createRecords( KEYWORD_TBL );
	foreach( $arr as $val )
	{
		try {
			$val->reservation();
		}
		catch( Exception $e ) {
			// 無視
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