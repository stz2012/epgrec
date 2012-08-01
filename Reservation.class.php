<?php
include_once('config.php');
include_once( INSTALL_PATH . "/DBRecord.class.php" );
include_once( INSTALL_PATH . "/reclib.php" );
include_once( INSTALL_PATH . "/Settings.class.php" );
include_once( INSTALL_PATH . "/recLog.inc.php" );

// 後方互換性
if( !defined( "RECORDER_CMD" ) ) {
	define( "RECORDER_CMD", INSTALL_PATH."/recorder.php" );
}


// 予約クラス

class Reservation {
	
	public static function simple( $program_id , $autorec = 0, $mode = 0) {
		$settings = Settings::factory();
		$rval = 0;
		try {
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
				$mode );
				
		}
		catch( Exception $e ) {
			throw $e;
		}
		return $rval;
	}
	
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

		// 時間を計算
		$start_time = toTimestamp( $starttime );
		$end_time = toTimestamp( $endtime ) + $settings->extra_time;
		
		if( $start_time < (time() + PADDING_TIME + 10) ) {	// 現在時刻より3分先より小さい＝すでに開始されている番組
			$start_time = time() + PADDING_TIME + 10;		// 録画開始時間を3分10秒先に設定する
		}
		$at_start = $start_time - PADDING_TIME;
		$sleep_time = PADDING_TIME - $settings->former_time;
		$rec_start = $start_time - $settings->former_time;
		
		// durationを計算しておく
		$duration = $end_time - $rec_start;
		if( $duration < ($settings->former_time + 60) ) {	// 60秒以下の番組は弾く
			throw new Exception( "終わりつつある/終わっている番組です" );
		}
		
		$rrec = null;
		try {
			// 同一番組予約チェック
			if( $program_id ) {
				$num = DBRecord::countRecords( RESERVE_TBL, "WHERE program_id = '".$program_id."'" );
				if( $num ) {
					throw new Exception("同一の番組が録画予約されています");
				}
			}
			
			$crec = new DBRecord( CHANNEL_TBL, "id", $channel_id );
			
			// 既存予約数 = TUNER番号
			$tuners = ($crec->type == "GR") ? (int)($settings->gr_tuners) : (int)($settings->bs_tuners);
			$type_str = ($crec->type == "GR") ? "type = 'GR' " : "(type = 'BS' OR type = 'CS') ";
			
			$battings = DBRecord::countRecords( RESERVE_TBL, "WHERE complete = '0' ".
															  "AND ".$type_str.
															  "AND starttime < '".toDatetime($end_time) ."' ".
															  "AND endtime > '".toDatetime($rec_start)."'"
			);
			
			if( $battings >= $tuners ) {
				// 重複を発見した
				if( $settings->force_cont_rec == 1 ) {
					// 解消可能な重複かどうかを調べる
					// 前後の予約数
					$nexts = DBRecord::countRecords( RESERVE_TBL, "WHERE complete = '0' ".
																	"AND ".$type_str.
																	"AND starttime = '".toDatetime($end_time - $settings->former_time)."'");
					
					$prevs = DBRecord::countRecords( RESERVE_TBL, "WHERE complete = '0' ".
																"AND ".$type_str.
																"AND endtime = '".$starttime."'"  );
					
					// 前後を引いてもチューナー数と同数以上なら重複の解消は無理
					if( ($battings - $nexts - $prevs) >= $tuners )
						throw new Exception( "重複予約を解消できません" );
					
					// 直後の番組はあるか?
					if( $nexts ) {
						// この番組の終わりをちょっとだけ早める
						$end_time = $end_time - $settings->former_time - $settings->rec_switch_time;
						$duration = $end_time - $rec_start;		// durationを計算しなおす
					}
					$battings -= $nexts;
					
					// 直前の録画予約を見付ける
					$trecs = DBRecord::createRecords(RESERVE_TBL, "WHERE complete = '0' ".
																		 "AND ".$type_str.
																		 "AND endtime = '".$starttime."'" );
					// 直前の番組をずらす
					for( $i = 0; $i < count($trecs) ; $i++ ) {
						if( $battings < $tuners ) break;	// 解消終了のハズ?
						// 予約修正に必要な情報を取り出す
						$prev_id           = $trecs[$i]->id;
						$prev_program_id   = $trecs[$i]->program_id;
						$prev_channel_id   = $trecs[$i]->channel_id;
						$prev_title        = $trecs[$i]->title;
						$prev_description  = $trecs[$i]->description;
						$prev_category_id  = $trecs[$i]->category_id;
						$prev_starttime    = $trecs[$i]->starttime;
						$prev_endtime      = $trecs[$i]->endtime;
						$prev_autorec      = $trecs[$i]->autorec;
						$prev_mode         = $trecs[$i]->mode;
						$prev_dirty        = $trecs[$i]->dirty;
						
						$prev_start_time = toTimestamp($prev_starttime);
						// 始まっていない予約？
						if( $prev_start_time > (time() + PADDING_TIME + $settings->former_time) ) {
							// 開始時刻を元に戻す
							$prev_starttime = toDatetime( $prev_start_time + $settings->former_time );
							// 終わりをちょっとだけずらす
							$prev_endtime   = toDatetime( toTimestamp($prev_endtime) - $settings->former_time - $settings->rec_switch_time );
							
							// tryのネスト
							try {
								// いったん予約取り消し
								self::cancel( $prev_id );
								// 再予約
								self::custom( 
									$prev_starttime,			// 開始時間Datetime型
									$prev_endtime,				// 終了時間Datetime型
									$prev_channel_id,			// チャンネルID
									$prev_title,				// タイトル
									$prev_description,			// 概要
									$prev_category_id,			// カテゴリID
									$prev_program_id,			// 番組ID
									$prev_autorec,				// 自動録画
									$prev_mode,
									$prev_dirty );
							}
							catch( Exception $e ) {
								throw new Exception( "重複予約を解消できません" );
							}
						}
						else {
							throw new Exception( "重複予約を解消できません" );
						}
						$battings--;
					}
					if( $battings < 0 ) $battings = 0;
					// これで重複解消したはず
				}
				else {
					throw new Exception( "重複予約があります" );
				}
			}
			// チューナー番号
			$tuner = $battings;
			
			// 改めてdurationをチェックしなおす
			if( $duration < ($settings->former_time + 60) ) {	// 60秒以下の番組は弾く
				throw new Exception( "終わりつつある/終わっている番組です" );
			}
			
			// ここからファイル名生成
/*
			%TITLE%	番組タイトル
			%ST%	開始日時（ex.200907201830)
			%ET%	終了日時
			%TYPE%	GR/BS/CS
			%CH%	チャンネル番号
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
			
			// %TITLE%
			$filename = mb_str_replace("%TITLE%", trim($title), $filename);
			// %ST%	開始日時
			$filename = mb_str_replace("%ST%",date("YmdHis", $start_time), $filename );
			// %ET%	終了日時
			$filename = mb_str_replace("%ET%",date("YmdHis", $end_time), $filename );
			// %TYPE%	GR/BS
			$filename = mb_str_replace("%TYPE%",$crec->type, $filename );
			// %CH%	チャンネル番号
			$filename = mb_str_replace("%CH%","".$crec->channel, $filename );
			// %DOW%	曜日（Sun-Mon）
			$filename = mb_str_replace("%DOW%",date("D", $start_time), $filename );
			// %DOWJ%	曜日（日-土）
			$filename = mb_str_replace("%DOWJ%",$day_of_week[(int)date("w", $start_time)], $filename );
			// %YEAR%	開始年
			$filename = mb_str_replace("%YEAR%",date("Y", $start_time), $filename );
			// %MONTH%	開始月
			$filename = mb_str_replace("%MONTH%",date("m", $start_time), $filename );
			// %DAY%	開始日
			$filename = mb_str_replace("%DAY%",date("d", $start_time), $filename );
			// %HOUR%	開始時
			$filename = mb_str_replace("%HOUR%",date("H", $start_time), $filename );
			// %MIN%	開始分
			$filename = mb_str_replace("%MIN%",date("i", $start_time), $filename );
			// %SEC%	開始秒
			$filename = mb_str_replace("%SEC%",date("s", $start_time), $filename );
			// %DURATION%	録画時間（秒）
			$filename = mb_str_replace("%DURATION%","".$duration, $filename );
			
			// あると面倒くさそうな文字を全部_に
//			$filename = preg_replace("/[ \.\/\*:<>\?\\|()\'\"&]/u","_", trim($filename) );
			
			// preg_replaceがUTF-8に対応できない環境があるようなのでmb_ereg_replaceに戻す
			$filename = mb_ereg_replace("[ \./\*:<>\?\\|()\'\"&]","_", trim($filename) );
			
			// 文字コード変換
			if( defined("FILESYSTEM_ENCODING") ) {
				$filename = mb_convert_encoding( $filename, FILESYSTEM_ENCODING, "UTF-8" );
			}
			
			$filename .= $RECORD_MODE["$mode"]['suffix'];
			$thumbname = $filename.".jpg";
			
			// サムネール
			$gen_thumbnail = INSTALL_PATH."/gen-thumbnail.sh";
			if( defined("GEN_THUMBNAIL") ) 
				$gen_thumbnail = GEN_THUMBNAIL;
			
			// ファイル名生成終了
			
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
			if( is_resource( $process ) ) {
				fwrite($pipes[0], RECORDER_CMD." ".$rrec->id."\n" );
				fclose($pipes[0]);
				// 標準エラーを取る
				$rstring = stream_get_contents($pipes[2]);
				
			    fclose( $pipes[2] );
			    fclose( $pipes[1] );
			    proc_close( $process );
			}
			else {
				$rrec->delete();
				reclog( "Reservation::custom atの実行に失敗した模様", EPGREC_ERROR);
				throw new Exception("AT実行エラー");
			}
			// job番号を取り出す
			$rarr = array();
			$tok = strtok( $rstring, " \n" );
			while( $tok !== false ) {
				array_push( $rarr, $tok );
				$tok = strtok( " \n" );
			}
			$key = array_search("job", $rarr);
			if( $key !== false ) {
				if( is_numeric( $rarr[$key+1]) ) {
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
		catch( Exception $e ) {
			if( $rrec != null ) {
				if( $rrec->id ) {
					// 予約を取り消す
					$rrec->delete();
				}
			}
			throw $e;
		}
	}
	// custom 終了
	
	// 取り消し
	public static function cancel( $reserve_id = 0, $program_id = 0 ) {
		$settings = Settings::factory();
		$rec = null;
		
		try {
			if( $reserve_id ) {
				$rec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
			}
			else if( $program_id ) {
				$rec = new DBRecord( RESERVE_TBL, "program_id" , $program_id );
			}
			if( $rec == null ) {
				throw new Exception("IDの指定が無効です");
			}
			if( ! $rec->complete ) {
				if( toTimestamp($rec->starttime) < (time() + PADDING_TIME + $settings->former_time) ) {
					reclog("Reservation::cancel 実行中の予約ID".$rec->id."の取り消しが実行された" );
					
					// recorderとの通信を試みる
					$ipc_key = ftok( RECORDER_CMD, "R" );
					
					/* php 5.3以降じゃないとmsg_queue_existsは使えない
					if( ! msg_queue_exists( $ipc_key ) ) {
						// メッセージキューがない
						reclog( "Reservation::cancel 実行中と推測される予約".$rec->id."が実行されていない", EPGREC_ERROR );
						$rec->complete = 1;
						throw new RecException( "Reserve:: 実行中と推測される予約が実行されていません。再度、削除を試みてください。", EPGREC_ERROR );
					}
					else {
					*/
						$msgh_r = msg_get_queue( $ipc_key );
						$ipc_key = ftok( RECORDER_CMD, "W" );
						$msgh_w = msg_get_queue( $ipc_key );
						
						// 終了を指示
						msg_send( $msgh_r, (int)$rec->id, "terminate" );
						sleep(1);
						for( $i = 0; $i < 60; $i++ ) {
							$r = msg_receive($msgh_w, (int)$rec->id , $msgtype, 1024, $message, TRUE, MSG_IPC_NOWAIT | MSG_NOERROR);
							if( $r ) {
								if( $message == "success" ) {
									reclog( "Reserve:: 実行中の予約ID".$rec->id."の取り消しに成功した模様" );
									break;
								}
								else if( $message == "error" ){
									reclog( "Reserve:: 実行中の予約ID".$rec->id."の取り消しに失敗", EPGREC_ERROR );
									throw new RecException("実行中の予約取り消しに失敗しました。しばらく時間をおいてから再度、取り消してください", EPGREC_ERROR );
								}
								// それ以外のメッセージは無視して待つ
							}
							sleep(1);
						}
						if( $i >= 60 ) throw new RecException("実行中の予約取り消しに失敗しました。しばらく時間をおいてから再度、取り消してください", EPGREC_ERROR );
//					}
				}
				else {
					// まだ実行されていない予約ならatを削除しとく
					exec( $settings->atrm . " " . $rec->job );
					reclog("Reservation::cancel ジョブ番号".$rec->job."を削除");
					$rec->delete();
				}
			}
			else {
				// 録画済み予約ならただ消す
				$rec->delete();
			}
		}
		catch( Exception $e ) {
			reclog("Reservation::cancel 予約キャンセルでDB接続またはアクセスに失敗した模様", EPGREC_ERROR );
			throw $e;
		}
	}
}
?>