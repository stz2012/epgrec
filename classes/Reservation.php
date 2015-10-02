<?php
// 予約クラス
class Reservation extends ModelBase
{
	// 簡易予約
	public static function simple( $program_id , $autorec = 0, $mode = 0)
	{
		$settings = Settings::factory();
		$rval = 0;

		try
		{
			$prec = new DBRecord( PROGRAM_TBL, "id", $program_id );
			$rval = self::custom(
				$prec->starttime,
				$prec->endtime,
				$prec->channel_id,
				$prec->title,
				$prec->description,
				$prec->category_id,
				$program_id,
				$autorec,
				$mode
			);
		}
		catch ( Exception $e )
		{
			throw $e;
		}

		return $rval;
	}

	// カスタマイズ予約
	public static function custom(
		$starttime,				// 開始時間Datetime型
		$endtime,				// 終了時間Datetime型
		$channel_id,			// チャンネルID
		$title = "none",		// タイトル
		$description = "none",	// 概要
		$category_id = 0,		// カテゴリID
		$program_id = 0,		// 番組ID
		$autorec = 0,			// 自動録画
		$mode = 0,				// 録画モード
		$dirty = 0				// ダーティフラグ
	) {
		global $RECORD_MODE;
		$settings = Settings::factory();
		$rrec = null;

		// 時間を計算
		$start_time = toTimestamp( $starttime );
		$end_time = toTimestamp( $endtime ) + $settings->extra_time;
		if ( $start_time < (time() + PADDING_TIME + 10) )
		{	// 現在時刻より3分先より小さい＝すでに開始されている番組
			$start_time = time() + PADDING_TIME + 10;		// 録画開始時間を3分10秒先に設定する
		}
		$at_start = $start_time - PADDING_TIME;
		$sleep_time = PADDING_TIME - $settings->former_time;
		$rec_start = $start_time - $settings->former_time;

		// durationを計算しておく
		$duration = $end_time - $rec_start;
		if ( $duration < ($settings->former_time + 60) )
		{	// 60秒以下の番組は弾く
			throw new Exception( "終わりつつある/終わっている番組です" );
		}

		try
		{
			// 同一番組予約チェック
			if ( $program_id )
			{
				$num = DBRecord::countRecords( RESERVE_TBL, "WHERE program_id = '{$program_id}'" );
				if ( $num )
					throw new Exception("同一の番組が録画予約されています");
			}
			
			$crec = new DBRecord( CHANNEL_TBL, "id", $channel_id );

			// 既存予約数 = TUNER番号
			$tuners = ($crec->type == "GR") ? (int)($settings->gr_tuners) : (int)($settings->bs_tuners);

			// 影響する予約情報を集める
			$options = "WHERE complete = '0'";
			$options .= " AND " . ($crec->type == "GR") ? "type = 'GR' " : "(type = 'BS' OR type = 'CS')";
			$options .= " AND starttime < CAST('".toDatetime($end_time)."' AS TIMESTAMP)";
			$options .= " AND endtime > CAST('".toDatetime($rec_start)."' AS TIMESTAMP)";
			$trecs = DBRecord::createRecords(RESERVE_TBL, $options);
			// 情報を配列に入れる
			for ( $i = 0; $i < count($trecs) ; $i++ )
			{
				$dim_start_time[$i] = toTimestamp($trecs[$i]->starttime);
				$dim_end_time[$i] = toTimestamp($trecs[$i]->endtime);
			}
			// 新規予約の値も配列に追加
			$dim_start_time[count($trecs)] = $rec_start;
			$dim_end_time[count($trecs)] = $end_time;

			// 配列を使って重複を調べ、重複解消を検証する
			$battings = 0;
			$mi = 0;
			for ( $i = 0; $i <= count($trecs) ; $i++ )
			{
				$mem_battings = 0;
				for ( $j = 0; $j <= count($trecs) ; $j++ )
				{
					if ( ( $i <> $j ) && ( $dim_start_time[$j] < $dim_end_time[$i] ) && ( $dim_end_time[$j] >= $dim_end_time[$i] ) )
					{
						$mem_battings++; // 重複をカウント
					}
				}
				if ( $mem_battings > $tuners )
				{	// 重複が多すぎるので予約不可
					throw new Exception( " 重複予約があります" );
				}
				// チューナー数が足りないとき、連続予約="する"なら重複解消を試みる
				if ( ( $mem_battings >= $tuners ) && ( $settings->force_cont_rec == 1 ) )
				{
					for ( $j = 0; $j <= count($trecs) ; $j++ )
					{
						// 連続予約があるか？
						if ( ( $i <> $j ) && ( $dim_end_time[$i] > $dim_start_time[$j] - $settings->rec_switch_time )
							&& ( $dim_end_time[$i] <= $dim_start_time[$j] + $settings->extra_time + $settings->former_time ) )
						{
							// 録画が始まっていないか？
							if ( $dim_start_time[$i] > ( time() + PADDING_TIME + $settings->former_time + $settings->rec_switch_time ) + 1 )
							{
								$mem[$mi] = $i;	// 変更すべき予約IDをメモ
								$dim_end_time[$i] = $dim_start_time[$j] - $settings->rec_switch_time; // 先行予約の終了時刻を早める
							}
							else
							{
								$mem[$mi] = $j;	// 変更すべき予約IDをメモ
								$dim_start_time[$j] = $dim_end_time[$i] + $settings->rec_switch_time; // 後続予約の開始時刻を遅くする
							}
							$mi++;
							$mem_battings--;
							break;
						}
					}
				}
				if ( $mem_battings >= $tuners )
				{	// 重複解消できない
					for ( $j = 0; $j < count($trecs) ; $j++ )
					{
						if ( ( $dim_start_time[$j] < $dim_end_time[$i] ) && ( $dim_end_time[$j] >= $dim_end_time[$i] ) )
						{
							 $msg = $msg."\n  「".$trecs[$j]->title."」";
						}
					}
					throw new Exception( " 予約が重複しています".$msg );
				}
				if ( $battings < $mem_battings )
				{
					$battings = $mem_battings;
				}
			}

			// ここまでくれば予約可能
			for ( $i = 0; $i < $mi ; $i++ )
			{	// 重複解消が必要なら実行する
				if ( $mem[$i] == count($trecs) )
				{	// 変更すべきは新規予約
					$rec_start = $dim_start_time[$mem[$i]];
					$end_time = $dim_end_time[$mem[$i]];
					$duration = $end_time - $rec_start;	// durationを計算しなおす
				}
				else
				{	// 変更すべきは既存予約
					// 予約修正に必要な情報を取り出す
					$prev_id           = $trecs[$mem[$i]]->id;
					$prev_program_id   = $trecs[$mem[$i]]->program_id;
					$prev_channel_id   = $trecs[$mem[$i]]->channel_id;
					$prev_title        = $trecs[$mem[$i]]->title;
					$prev_description  = $trecs[$mem[$i]]->description;
					$prev_category_id  = $trecs[$mem[$i]]->category_id;
					$prev_starttime    = $trecs[$mem[$i]]->starttime;
					$prev_endtime      = $trecs[$mem[$i]]->endtime;
					$prev_autorec      = $trecs[$mem[$i]]->autorec;
					$prev_mode         = $trecs[$mem[$i]]->mode;
					$prev_dirty        = $trecs[$mem[$i]]->dirty;
					$prev_start_time = toTimestamp($prev_starttime);
					// 開始時刻を再設定
					$prev_starttime = toDatetime( $dim_start_time[$mem[$i]] + $settings->former_time );
					// 終了時刻を再設定
					$prev_endtime   = toDatetime( $dim_end_time[$mem[$i]] );
					// tryのネスト
					try
					{
						self::cancel( $prev_id );	// いったん予約取り消し
						self::custom( 			// 再予約
							$prev_starttime,	// 開始時間Datetime型
							$prev_endtime,		// 終了時間Datetime型
							$prev_channel_id,	// チャンネルID
							$prev_title,		// タイトル
							$prev_description,	// 概要
							$prev_category_id,	// カテゴリID
							$prev_program_id,	// 番組ID
							$prev_autorec,		// 自動録画
							$prev_mode,
							$prev_dirty );
					}
					catch ( Exception $e )
					{
						throw new Exception( " 予約時刻変更(再予約)に失敗しました\n  「".$prev_title."」" );
					}
				}
			}

			// チューナー番号
			$tuner = $battings;

			// 改めてdurationをチェックしなおす
			if ( $duration < ($settings->former_time + 60) )
			{	// 60秒以下の番組は弾く
				throw new Exception( "終わりつつある/終わっている番組です" );
			}

			// ここからファイル名生成
/*
			%TITLE%	番組タイトル
			%ST%	開始日時（ex.200907201830)
			%ET%	終了日時
			%TYPE%	GR/BS/CS
			%CH%	チャンネル番号
			%SID%	サービスID
			%DOW%	曜日（Sun-Mon）
			%DOWJ%	曜日（日-土）
			%YEAR%	開始年
			%MONTH%	開始月
			%DAY%	開始日
			%HOUR%	開始時
			%MIN%	開始分
			%SEC%	開始秒
			%DURATION%	録画時間（秒）
*/
			$day_of_week = array( "日","月","火","水","木","金","土" );
			$filename = $settings->filename_format;
			// %TITLE%	番組タイトル
			$filename = self::_mb_str_replace("%TITLE%", trim($title), $filename);
			// %ST%	開始日時
			$filename = self::_mb_str_replace("%ST%",date("YmdHis", $start_time), $filename );
			// %ET%	終了日時
			$filename = self::_mb_str_replace("%ET%",date("YmdHis", $end_time), $filename );
			// %TYPE%	GR/BS/CS
			$filename = self::_mb_str_replace("%TYPE%",$crec->type, $filename );
			// %CH%	チャンネル番号
			$filename = self::_mb_str_replace("%CH%","".$crec->channel, $filename );
			// %SID%	サービスID
			$filename = self::_mb_str_replace("%SID%","".$crec->sid, $filename );
			// %DOW%	曜日（Sun-Mon）
			$filename = self::_mb_str_replace("%DOW%",date("D", $start_time), $filename );
			// %DOWJ%	曜日（日-土）
			$filename = self::_mb_str_replace("%DOWJ%",$day_of_week[(int)date("w", $start_time)], $filename );
			// %YEAR%	開始年
			$filename = self::_mb_str_replace("%YEAR%",date("Y", $start_time), $filename );
			// %MONTH%	開始月
			$filename = self::_mb_str_replace("%MONTH%",date("m", $start_time), $filename );
			// %DAY%	開始日
			$filename = self::_mb_str_replace("%DAY%",date("d", $start_time), $filename );
			// %HOUR%	開始時
			$filename = self::_mb_str_replace("%HOUR%",date("H", $start_time), $filename );
			// %MIN%	開始分
			$filename = self::_mb_str_replace("%MIN%",date("i", $start_time), $filename );
			// %SEC%	開始秒
			$filename = self::_mb_str_replace("%SEC%",date("s", $start_time), $filename );
			// %DURATION%	録画時間（秒）
			$filename = self::_mb_str_replace("%DURATION%","".$duration, $filename );
			// あると面倒くさそうな文字を全部_に
//			$filename = preg_replace("/[ \.\/\*:<>\?\\|()\'\"&]/u","_", trim($filename) );
			// preg_replaceがUTF-8に対応できない環境があるようなのでmb_ereg_replaceに戻す
			$filename = mb_ereg_replace("[ \./\*:<>\?\\|()\'\"&]","_", trim($filename) );

			// 文字コード変換
			if ( defined("FILESYSTEM_ENCODING") )
			{
				$filename = mb_convert_encoding( $filename, FILESYSTEM_ENCODING, "UTF-8" );
			}
			$filename .= $RECORD_MODE["$mode"]['suffix'];
			$thumbname = $filename.".jpg";

			// サムネール
			$gen_thumbnail = INSTALL_PATH."/scripts/gen-thumbnail.sh";
			if ( defined("GEN_THUMBNAIL") ) 
				$gen_thumbnail = GEN_THUMBNAIL;

			// 予約レコードを埋める
			$rrec = new DBRecord( RESERVE_TBL );
			$rrec->channel_disc = $crec->channel_disc;
			$rrec->channel_id = $crec->id;
			$rrec->program_id = $program_id;
			$rrec->type = $crec->type;
			$rrec->channel = $crec->channel;
			$rrec->title = $title;
			$rrec->description = $description;
			$rrec->category_id = $category_id;
			$rrec->starttime = toDatetime( $rec_start );
			$rrec->endtime = toDatetime( $end_time );
			$rrec->path = $filename;
			$rrec->autorec = $autorec;
			$rrec->mode = $mode;
			$rrec->reserve_disc = md5( $crec->channel_disc . toDatetime( $start_time ). toDatetime( $end_time ) );
			$rrec->update();

			// 予約実行
			$cmdline = $settings->at." ".date("H:i m/d/Y", $at_start);
			$descriptor = array( 0 => array( "pipe", "r" ),
								 1 => array( "pipe", "w" ),
								 2 => array( "pipe", "w" ),
			);
			$env = array( "CHANNEL"  => $crec->channel,
						  "DURATION" => $duration,
						  "OUTPUT"   => INSTALL_PATH.$settings->spool."/".$filename,
						  "TYPE"     => $crec->type,
						  "TUNER"    => $tuner,
						  "MODE"     => $mode,
						  "THUMB"    => INSTALL_PATH.$settings->thumbs."/".$thumbname,
						  "FORMER"   => "".$settings->former_time,
						  "FFMPEG"   => "".$settings->ffmpeg,
						  "SID"      => $crec->sid,
			);

			// ATで予約する
			$process = proc_open( $cmdline , $descriptor, $pipes, INSTALL_PATH.$settings->spool, $env );
			if ( is_resource( $process ) )
			{
				fwrite($pipes[0], RECORDER_CMD." ".$rrec->id."\n" );
				fclose($pipes[0]);
				// 標準エラーを取る
				$rstring = stream_get_contents($pipes[2]);
				fclose( $pipes[2] );
				fclose( $pipes[1] );
				proc_close( $process );
			}
			else
			{
				$rrec->delete();
				reclog( "Reservation::custom atの実行に失敗した模様", EPGREC_ERROR);
				throw new Exception("AT実行エラー");
			}

			// job番号を取り出す
			$rarr = array();
			$tok = strtok( $rstring, " \n" );
			while ( $tok !== false )
			{
				array_push( $rarr, $tok );
				$tok = strtok( " \n" );
			}
			$key = array_search("job", $rarr);
			if ( $key !== false )
			{
				if ( is_numeric( $rarr[$key+1]) )
				{
					$rrec->job = $rarr[$key+1];
					reclog( "Reservation::custom ジョブ番号".$rrec->job."に録画ジョブを登録");
					return $rrec->job;			// 成功
				}
			}

			// エラー
			$rrec->delete();
			reclog( "Reservation::custom job番号の取得に失敗",EPGREC_ERROR );
			throw new Exception( "job番号の取得に失敗" );
		}
		catch ( Exception $e )
		{
			if ( $rrec != null )
			{
				if ( $rrec->id )
				{
					// 予約を取り消す
					$rrec->delete();
				}
			}
			throw $e;
		}
	}

	// キーワード自動予約
	public static function keyword( $keyword_id, $isCancel=false )
	{
		try
		{
			$rec = new DBRecord( KEYWORD_TBL, "id", $keyword_id );

			$programs = self::getSearchData(
				$rec->keyword,
				$rec->use_regexp,
				$rec->type,
				$rec->channel_id,
				$rec->category_id,
				$rec->prgtime,
				$rec->weekofday
			);

			foreach ( $programs as $r )
			{
				try
				{
					if ( $isCancel )
					{
						$reserve = new DBRecord( RESERVE_TBL, "program_id", $r['id'] );
						// 自動予約されたもののみ削除
						if ( $reserve->autorec )
						{
							self::cancel( $reserve->id );
						}
					}
					else
					{
						if ( $r['autorec'] )
						{
							self::simple( $r['id'], $rec->id, $rec->autorec_mode );
							reclog( "Reservation::keyword キーワードID".$rec->id."の録画が予約された");
						}
					}
					usleep( 100 ); // あんまり時間を空けないのもどう?
				}
				catch ( Exception $e )
				{
					// 無視
				}
			}
		}
		catch ( Exception $e )
		{
			reclog("Reservation::keyword キーワード自動予約でDB接続またはアクセスに失敗した模様", EPGREC_ERROR );
			throw $e;
		}
	}

	// 予約キャンセル
	public static function cancel( $reserve_id = 0, $program_id = 0 )
	{
		$settings = Settings::factory();
		$rec = null;

		try
		{
			if ( $reserve_id )
			{
				$rec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
			}
			else if ( $program_id )
			{
				$rec = new DBRecord( RESERVE_TBL, "program_id" , $program_id );
			}
			if ( $rec == null )
			{
				throw new Exception("IDの指定が無効です");
			}
			if ( ! $rec->complete )
			{
				if ( toTimestamp($rec->starttime) < (time() + PADDING_TIME + $settings->former_time) )
				{
					reclog("Reservation::cancel 実行中の予約ID".$rec->id."の取り消しが実行された" );

					// recorderとの通信を試みる
					$ipc_key = ftok( RECORDER_CMD, "R" );
					
					/* php 5.3以降じゃないとmsg_queue_existsは使えない
					if ( ! msg_queue_exists( $ipc_key ) )
					{
						// メッセージキューがない
						reclog( "Reservation::cancel 実行中と推測される予約".$rec->id."が実行されていない", EPGREC_ERROR );
						$rec->complete = 1;
						throw new RecException( "Reserve:: 実行中と推測される予約が実行されていません。再度、削除を試みてください。", EPGREC_ERROR );
					}
					else
					{
					*/
						$msgh_r = msg_get_queue( $ipc_key );
						$ipc_key = ftok( RECORDER_CMD, "W" );
						$msgh_w = msg_get_queue( $ipc_key );

						// 終了を指示
						msg_send( $msgh_r, (int)$rec->id, "terminate" );
						sleep(1);
						for ( $i = 0; $i < 60; $i++ )
						{
							$r = msg_receive($msgh_w, (int)$rec->id , $msgtype, 1024, $message, TRUE, MSG_IPC_NOWAIT | MSG_NOERROR);
							if ( $r )
							{
								if ( $message == "success" )
								{
									reclog( "Reserve:: 実行中の予約ID".$rec->id."の取り消しに成功した模様" );
									break;
								}
								else if ( $message == "error" )
								{
									reclog( "Reserve:: 実行中の予約ID".$rec->id."の取り消しに失敗", EPGREC_ERROR );
									throw new RecException("実行中の予約取り消しに失敗しました。しばらく時間をおいてから再度、取り消してください", EPGREC_ERROR );
								}
								// それ以外のメッセージは無視して待つ
							}
							sleep(1);
						}
						if ( $i >= 60 )
							throw new RecException("実行中の予約取り消しに失敗しました。しばらく時間をおいてから再度、取り消してください", EPGREC_ERROR );
//					}
				}
				else
				{
					// まだ実行されていない予約ならatを削除しとく
					exec( $settings->atrm . " " . $rec->job );
					reclog("Reservation::cancel ジョブ番号".$rec->job."を削除");
					$rec->delete();
				}
			}
			else
			{
				// 録画済み予約ならただ消す
				$rec->delete();
			}
		}
		catch ( Exception $e )
		{
			reclog("Reservation::cancel 予約キャンセルでDB接続またはアクセスに失敗した模様", EPGREC_ERROR );
			throw $e;
		}
	}

	/**
	 * 番組検索データ取得
	 * @return array
	 */
	public static function getSearchData(
		$keyword = "", 
		$use_regexp = false,
		$tuner_type = "*", 
		$channel_id = 0,
		$category_id = 0,
		$prgtime = 24,
		$weekofday = 0,
		$limit = 300
	) {
		$settings = Settings::factory();
		$program_data = array();

		$sql = "SELECT a.*, b.name AS station_name,";
		$sql .= " c.name_en AS cat, COALESCE(d.rsv_cnt, 0) AS rec";
		$sql .= "  FROM {$settings->tbl_prefix}".PROGRAM_TBL." a";
		$sql .= "  LEFT JOIN {$settings->tbl_prefix}".CHANNEL_TBL." b";
		$sql .= "    ON b.id = a.channel_id";
		$sql .= "  LEFT JOIN {$settings->tbl_prefix}".CATEGORY_TBL." c";
		$sql .= "    ON c.id = a.category_id";
		$sql .= "  LEFT JOIN (";
		$sql .= "    SELECT program_id, COUNT(*) AS rsv_cnt";
		$sql .= "      FROM {$settings->tbl_prefix}".RESERVE_TBL;
		$sql .= "     GROUP BY program_id";
		$sql .= "  ) d";
		$sql .= "    ON d.program_id = a.id";
		$sql .= " WHERE starttime > CAST(:search_time AS TIMESTAMP)";
		if ( $keyword != "" )
		{
			if ( $use_regexp )
			{
				if (self::getDbType() == 'pgsql')
					$sql .= " AND title || description ~ :keyword";
				else if (self::getDbType() == 'sqlite')
					$sql .= " AND title || description REGEXP :keyword";
				else
					$sql .= " AND CONCAT(title,description) REGEXP :keyword";
			}
			else
			{
				if (self::getDbType() == 'pgsql' || self::getDbType() == 'sqlite')
					$sql .= " AND title || description LIKE :keyword";
				else
					$sql .= " AND CONCAT(title,description) LIKE :keyword";
			}
		}
		if ( $tuner_type != "*" )
			$sql .= " AND type = :tuner_type";
		if ( $channel_id != 0 )
			$sql .= " AND channel_id = :channel_id";
		if ( $category_id != 0 )
			$sql .= " AND category_id = :category_id";
		if ( $prgtime != 24 )
			$sql .= " AND CAST(starttime AS TIME) BETWEEN CAST(:prgtime_from AS TIME) AND CAST(:prgtime_to AS TIME)";
		if ( $weekofday != 0 )
		{
			if (self::getDbType() == 'pgsql')
				$sql .= " AND EXTRACT(dow from starttime) = :weekofday";
			else if (self::getDbType() == 'sqlite')
				$sql .= " AND CAST(strftime('%w', starttime) AS INTEGER) + 1 = :weekofday";
			else
				$sql .= " AND DAYOFWEEK(starttime) = :weekofday";
		}
		$sql .= " ORDER BY starttime ASC";
		$sql .= " LIMIT :search_limit";
		$stmt = self::$connInst->prepare($sql);
		$stmt->bindValue(':search_time', date("Y-m-d H:i:s", time() + $settings->padding_time + 60));
		if ( $keyword != "" )
		{
			if ( $use_regexp )
				$stmt->bindValue(':keyword', $keyword);
			else
				$stmt->bindValue(':keyword', "%{$keyword}%");
		}
		if ( $tuner_type != "*" )
			$stmt->bindValue(':tuner_type', $tuner_type);
		if ( $channel_id != 0 )
			$stmt->bindValue(':channel_id', $channel_id, PDO::PARAM_INT);
		if ( $category_id != 0 )
			$stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		if ( $prgtime != 24 )
		{
			$stmt->bindValue(':prgtime_from', sprintf("%02d:00:00", $prgtime));
			$stmt->bindValue(':prgtime_to',   sprintf("%02d:59:59", $prgtime));
		}
		if ( $weekofday != 0 )
			$stmt->bindValue(':weekofday', $weekofday);
		$stmt->bindValue(':search_limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		$program_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return $program_data;
	}

	// マルチバイトstr_replace
	private static function _mb_str_replace($search, $replace, $target, $encoding = "UTF-8" )
	{
		$notArray = !is_array($target) ? TRUE : FALSE;
		$target = $notArray ? array($target) : $target;
		$search_len = mb_strlen($search, $encoding);
		$replace_len = mb_strlen($replace, $encoding);
		
		foreach ($target as $i => $tar)
		{
			$offset = mb_strpos($tar, $search);
			while ($offset !== FALSE)
			{
				$tar = mb_substr($tar, 0, $offset).$replace.mb_substr($tar, $offset + $search_len);
				$offset = mb_strpos($tar, $search, $offset + $replace_len);
			}
			$target[$i] = $tar;
		}
		return $notArray ? $target[0] : $target;
	}
}
?>
