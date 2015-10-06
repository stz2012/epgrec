#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');

$settings = Settings::factory();
$reserve_id = $argv[1];

try
{
	$msg_obj = new EpgrecMsg( $reserve_id );
	$rrec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
	$crec = new DBRecord( CHANNEL_TBL, "id" , $rrec->channel_id) ;

	// 時刻を得る
	$starttime = toTimestamp($rrec->starttime);
	$endtime   = toTimestamp($rrec->endtime);

	if ( time() > $starttime )
	{	// 過去の録画予約
		$rrec->complete = 1;	// 終わったことにする
		throw new RecException("recorder:: なぜか過去の録画予約が実行された", EPGREC_ERROR );
	}
	reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画ジョブ開始" );

	// tuner
	$options = "WHERE complete = '0'";
	$options .= " AND " . ($crec->type == "GR") ? "type = 'GR' " : "(type = 'BS' OR type = 'CS')";
	$options .= " AND id <> '{$rrec->id}'";
	if ($settings->db_type == 'pgsql')
	{
		$options .= " AND starttime < CAST('{$rrec->endtime}' AS TIMESTAMP)";
		$options .= " AND endtime > CAST('{$rrec->starttime}' AS TIMESTAMP)";
	}
	else if ($settings->db_type == 'sqlite')
	{
		$options .= " AND datetime(starttime) < datetime('{$rrec->endtime}')";
		$options .= " AND datetime(endtime) > datetime('{$rrec->starttime}')";
	}
	else
	{
		$options .= " AND starttime < CAST('{$rrec->endtime}' AS DATETIME)";
		$options .= " AND endtime > CAST('{$rrec->starttime}' AS DATETIME)";
	}
	$tuner = DBRecord::countRecords( RESERVE_TBL, $options );

	// program_start;
	$program_start = $starttime + (int)($settings->former_time);
	$env_rec = array(
		"CHANNEL"  => $rrec->channel,
		"DURATION" => $endtime - $starttime,
		"OUTPUT"   => INSTALL_PATH.$settings->spool."/".$rrec->path,
		"TYPE"     => $crec->type,
		"TUNER"    => $tuner,
		"MODE"     => $rrec->mode,
		"THUMB"    => INSTALL_PATH.$settings->thumbs."/".$rrec->path.".jpg",
		"FORMER"   => "".$settings->former_time,
		"FFMPEG"   => "".$settings->ffmpeg,
		"SID"      => $crec->sid,
		"START_TIME" => date( "YmdHis", $program_start ),
	);

	// 録画開始まで待つ
	while ( time() < $starttime )
	{
		if ( ($message = $msg_obj->recvMessage() ) != null )
		{
			switch( $message )
			{
				// 終了指示
				case "terminate":
					$msg_obj->sendMessage("success");
					$rrec->complete = 1;	// 終わったことにする
					throw new RecException("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画が中断された" );
					break;
				// ステータス
				case "stat":
					$msg_obj->sendMessage("alive");
					break;
				// 未定義
				default:
					break;
			}
		}
		usleep( 50 * 1000 );				// 50ミリ秒待つ
	}

	// 録画開始
	$proch = false;
	if ( ( $proch = EpgrecProcMng::execCommand(DO_RECORD, $env_rec) ) !== false )
	{
		reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画開始" );

		// 録画完了待ち
		$rec_cont = true;
		while ( $rec_cont )
		{
			$st = proc_get_status($proch);
			if (! $st['running'] ) $rec_cont = false;    // 録画完了

			if ( ($message = $msg_obj->recvMessage() ) != null )
			{
				switch( $message )
				{
					// 終了指示
					case "terminate":
						if ( $msg_obj->termProcess( $proch ) == false )
						{
							$msg_obj->sendMessage("error");
							reclog( "録画コマンドを停止できません", EPGREC_WARN );
						}
						else
						{
							$msg_obj->sendMessage("success");
							reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画が中断された" );
							$rec_cont = false;
						}
						break;
					// ステータス
					case "stat":
						$msg_obj->sendMessage("alive");
						break;
					// 未定義
					default:
						break;
				}
			}
			sleep(1);
		}
		proc_close( $proch );
		$proch = false;
	}
	else
	{
		$rrec->complete = 1;	// 終わったことにする
		throw new RecException("recorder:: 録画コマンドの実行に失敗した", EPGREC_ERROR );
	}

	// 予定より短いようなら終了時間を現在に書き換える
	if ( time() < $endtime )
	{
		$rrec->endtime = toDatetime( time() );
	}
	// 完了フラグを立てておく
	$rrec->complete = '1';

	// ちょっと待った方が確実っぽい
	sleep(15);
	@exec("sync");

	if ( file_exists( INSTALL_PATH .$settings->spool . "/". $rrec->path ) )
	{	// 予約完了
		reclog( "recorder:: 予約ID". $rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画終了" );

		// サムネール作成
		if ( $settings->use_thumbs == 1 )
		{
			$gen_thumbnail = INSTALL_PATH."/scripts/gen-thumbnail.sh";
			if ( defined("GEN_THUMBNAIL") ) 
				$gen_thumbnail = GEN_THUMBNAIL;
			@exec($gen_thumbnail);
		}

		// MediaTomb更新
		if ( $settings->mediatomb_update == 1 )
		{
			// タイトル更新
			$title = $rrec->title."(".date("Y/m/d").")";
			$db_obj->updateRow('mt_cds_object', array('dc_title' => $title),
														array('dc_title' => $rrec->path));
			// 説明更新
			$desc = "dc:description=".trim($rrec->description);
			$desc .= "&epgrec:id=".$reserve_id;
			$db_obj->updateRow('mt_cds_object', array('metadata' => $desc),
														array('dc_title' => $rrec->path));
		}
	}
	else
	{	// 予約失敗
		reclog( "recomplete:: 予約ID". $rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画に失敗した模様", EPGREC_ERROR );
	}

	$msg_obj = null;
}
catch ( Exception $e )
{
	reclog( "recorder:: ".$e->getMessage(), EPGREC_ERROR );
}

doPowerReduce();	// 省電力

exit();
?>
