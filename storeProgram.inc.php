<?php
include_once( INSTALL_PATH . "/reclib.php" );

function garbageClean() {
	// 不要なプログラムの削除
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

function doKeywordReservation() {
  // キーワード自動録画予約
 	$arr = array();
	$arr = Keyword::createKeywords();
	foreach( $arr as $val ) {
		try {
			$val->reservation();
		}
		catch( Exception $e ) {
			// 無視
		}
	}
}

function storeProgram( $type, $xmlfile ) {
	global $BS_CHANNEL_MAP, $GR_CHANNEL_MAP, $CS_CHANNEL_MAP;
	global $settings;
	
	// チャンネルマップファイルの準備
	$map = array();
	if( $type == "BS" ) $map = $BS_CHANNEL_MAP;
	else if( $type == "GR") $map = $GR_CHANNEL_MAP;
	else if( $type == "CS") $map = $CS_CHANNEL_MAP;
	
	// XML parse
  	$xml = @simplexml_load_file( $xmlfile );
	if( $xml === false ) {
		reclog( "getepg:: 正常な".$xmlfile."が作成されなかった模様(放送間帯でないなら問題ありません)", EPGREC_WARN );
		return;	// XMLが読み取れないなら何もしない
	}
	// channel抽出
	foreach( $xml->channel as $ch ) {
		$disc = $ch['id'];
	 try {
		// チャンネルデータを探す
		$num = DBRecord::countRecords( CHANNEL_TBL , "WHERE channel_disc = '" . $disc ."'" );
		if( $num == 0 ) {
			if( array_key_exists( "$disc", $map ) ) {
				// チャンネルデータがないなら新規作成
				$rec = new DBRecord( CHANNEL_TBL );
				$rec->type = $type;
				$rec->channel = $map["$disc"];
				$rec->channel_disc = $disc;
				$rec->name = $ch->{'display-name'};
			}
		}
		else {
			// 存在した場合も、とりあえずチャンネル名は更新する
			$rec = new DBRecord(CHANNEL_TBL, "channel_disc", $disc );
			$rec->name = $ch->{'display-name'};
		}
	 }
	 catch( Exception $e ) {
		reclog( "getepg::DBの接続またはチャンネルテーブルの書き込みに失敗", EPGREC_ERROR );
	 }
	}
	// channel 終了
	
	// programme 取得
	
	foreach( $xml->programme as $program ) {
		$channel_rec = null;
		
		$channel_disc = $program['channel']; 
		if( ! array_key_exists( "$channel_disc", $map ) ) continue;
		$channel = $map["$channel_disc"];
		
		try {
			$channel_rec = new DBRecord(CHANNEL_TBL, "channel_disc", "$channel_disc" );
		}
		catch( Exception $e ) {
			reclog( "getepg::チャンネルレコード $channel_disc が発見できない", EPGREC_ERROR );
		}
		if( $channel_rec == null ) continue;	// あり得ないことが起きた
		if( $channel_rec->skip == 1 ) continue;	// 受信しないチャンネル
		
		$starttime = str_replace(" +0900", '', $program['start'] );
		$endtime = str_replace( " +0900", '', $program['stop'] );
		$title = $program->title;
		$desc = $program->desc;
		$cat_ja = "";
		$cat_en = "";
		foreach( $program->category as $cat ) {
			if( $cat['lang'] == "ja_JP" ) $cat_ja = $cat;
			if( $cat['lang'] == "en" ) $cat_en = $cat;
		}
		$program_disc = md5( $channel_disc . $starttime . $endtime );
		// printf( "%s %s %s %s %s %s %s \n", $program_disc, $channel, $starttime, $endtime, $title, $desc, $cat_ja );
		
		// カテゴリ登録
		
		$cat_rec = null;
		try {
			// カテゴリを処理する
			$category_disc = md5( $cat_ja . $cat_en );
			$num = DBRecord::countRecords(CATEGORY_TBL, "WHERE category_disc = '".$category_disc."'" );
			if( $num == 0 ) {
				// 新規カテゴリの追加
				$cat_rec = new DBRecord( CATEGORY_TBL );
				$cat_rec->name_jp = $cat_ja;
				$cat_rec->name_en = $cat_en;
				$cat_rec->category_disc = $category_disc;
				reclog("getepg:: 新規カテゴリ".$cat_ja."を追加" );
			}
			else
				$cat_rec = new DBRecord(CATEGORY_TBL, "category_disc" , $category_disc );
		}
		catch( Exception $e ) {
			reclog("getepg:: カテゴリテーブルのアクセスに失敗した模様", EPGREC_ERROR );
			reclog("getepg:: ".$e->getMessage()."" ,EPGREC_ERROR );
			exit( $e->getMessage() );
		}
		
		// プログラム登録
		
		try {
			//
			$num = DBRecord::countRecords(PROGRAM_TBL, "WHERE program_disc = '".$program_disc."'" );
			if( $num == 0 ) {
				// 新規番組
				// 重複チェック 同時間帯にある番組
				$options = "WHERE channel_disc = '".$channel_disc."' ".
					"AND starttime < '". $endtime ."' AND endtime > '".$starttime."'";
				$battings = DBRecord::countRecords(PROGRAM_TBL, $options );
				if( $battings > 0 ) {
					// 重複発生＝おそらく放映時間の変更
					$records = DBRecord::createRecords(PROGRAM_TBL, $options);
					foreach( $records as $rec ) {
						// 自動録画予約された番組は放映時間変更と同時にいったん削除する
						try {
							$reserve = new DBRecord(RESERVE_TBL, "program_id", $rec->id );
							// すでに開始されている録画は無視する
							if( time() > (toTimestamp($reserve->starttime) - PADDING_TIME - $settings->former_time) ) {
								reclog( "getepg::録画ID".$reserve->id.":".$reserve->type.$reserve->channel.$reserve->title."は録画開始後に時間変更が発生した可能性がある", EPGREC_WARN );
							}
							else {
								if( $reserve->autorec ) {
									reclog( "getepg::録画ID".$reserve->id.":".$reserve->type.$reserve->channel.$reserve->title."は時間変更の可能性があり予約取り消し" );
									Reservation::cancel( $reserve->id );
								}
							}
						}
						catch( Exception $e ) {
							// 無視
						}
						// 番組削除
						reclog( "getepg::放送時間重複が発生した番組ID".$rec->id." ".$rec->type.$rec->channel.$rec->title."を削除" );
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
			else {
				// 番組内容更新
				$rec = new DBRecord( PROGRAM_TBL, "program_disc", $program_disc );
				$rec->title = $title;
				$rec->description = $desc;
				$rec->category_id = $cat_rec->id;
				$rec->update();
				try {
					$reserve = new DBRecord( RESERVE_TBL, "program_id", $rec->id );
					// dirtyが立っておらず現在より後の録画予約であるなら
					if( ($reserve->dirty == 0) && (toTimestamp($reserve->starttime) > time()) ) {
						$reserve->title = $title;
						$reserve->description = $desc;
						reclog( "getepg:: 予約ID".$reserve->id."のEPG情報が更新された" );
						$reserve->update();
					}
				}
				catch( Exception $e ) {
					// 無視する
				}
				// 書き込む
			}
		}
		catch(Exception $e) {
			reclog( "getepg:: プログラムテーブルに問題が生じた模様", EPGREC_ERROR );
			reclog( "getepg:: ".$e->getMessage()."" , EPGREC_ERROR);
			exit( $e->getMessage() );
		}
	}
	// Programme取得完了
}
?>
