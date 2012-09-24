#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/config.php');
include_once( INSTALL_PATH . "/DBRecord.class.php" );
include_once( INSTALL_PATH . "/Settings.class.php" );
include_once( INSTALL_PATH . "/recLog.inc.php" );
include_once( INSTALL_PATH . "/reclib.php" );

define("DEBUG", true );

// 後方互換性

if( ! defined( "RECORDER_CMD" ) ) {
	define( "RECORDER_CMD", INSTALL_PATH . "/recorder.php" );
}

$settings = Settings::factory();
$reserve_id = $argv[1];
$msgh_r = null;		// 受信用メッセージハンドラ
$msgh_w = null;		// 送信用メッセージハンドラ



$logfile = INSTALL_PATH."/settings/recorder_".$reserve_id.".log";

// ノンブロッキングメッセージ受信

function epgrec_get_message() {
	global $msgh_r, $reserve_id;
	
	$r = msg_receive($msgh_r, (int)$reserve_id , $msgtype, 1024, $message, TRUE, MSG_IPC_NOWAIT | MSG_NOERROR);
	if( $r ) return $message;
	
	return null;
}

// メッセージ送信

function epgrec_send_message( $msg ) {
	global $msgh_w, $reserve_id;
	
	msg_send( $msgh_w, (int)$reserve_id, $msg );
	sleep(1);	// 相手が受信してくれそうな時間だけ待つ
}


function epgrec_exec( $cmd, $env = null ) {
	$descspec = array(
                        0 => array( 'file','/dev/null','r' ),
                        1 => array( 'file','/dev/null','w' ),
                        2 => array( 'file','/dev/null','w' ),
	);
	$p = proc_open( $cmd, $descspec, $pipes, INSTALL_PATH, $env  );
	if( is_resource( $p ) ) return $p;
	
	return false;
}

// 指定したプロセスIDが生成した子プロセスのpidリストを返す
// こういうやり方しかないのか？
//
function epgrec_childproc( $ppid )
{
	// ps を実行する
	$d = array(
			0 => array( 'file','/dev/null','r' ),
			1 => array( 'pipe','w' ),
			2 => array( 'file','/dev/null','w' ),
	);
	
	$ps = proc_open( "/bin/ps -o pid,ppid ax" , $d, $pipes );
	do {
		$st = proc_get_status( $ps );
	}while( $st['running'] );
	
	// 標準出力を読む
	$cpids = array();
	while( ! feof( $pipes[1] ) ) {
		$line = trim(fgets( $pipes[1] ));
		$pids = preg_split( "/[\s]+/", $line );
		if( ! isset( $pids[1]) ) continue;
		if( $pids[1] == $ppid ) {
			array_push( $cpids, $pids[0] );
		}
	}
	fclose( $pipes[1] );
	proc_close( $ps );
	
	foreach( $cpids as $p ) {
		$ccpids = epgrec_childproc( $p );
		foreach( $ccpids as $ccpid ) {
			array_push( $cpids, $ccpid );
		}
	}
	return $cpids;
}

// 指定したプロセスハンドルを子プロセスを含め終了させる

function epgrec_termproc( $p )
{
	if( DEBUG ) {
		global $logfile;
		system( "ps ax >>".$logfile );
		system( "echo ------- >>".$logfile );
	}
	$status = proc_get_status( $p );
	$cpids = epgrec_childproc( $status['pid'] );
	
	if( DEBUG ) {
		 global $logfile;
		 
		 foreach( $cpids as $cpid ) {
			system( "echo ".$cpid." >>".$logfile );
		}
		system( "echo ------- >>".$logfile );
	}
	
	// 親から止める
	@proc_terminate( $p );
	usleep(500*1000);
	@proc_terminate( $p );	// 2度送る
	
	foreach( $cpids as $cpid ) {
		$ret = posix_kill( $cpid, SIGTERM );		// sigterm
		usleep(100*1000);
		if( ! $ret ) posix_kill( $cpid, SIGKILL );	// sigkill
	}
	
	if( DEBUG ) {
		global $logfile;
		system( "ps ax >>".$logfile );
		system( "echo ------- >>".$logfile );
	}
	
	/* プロセスがしばらく居残る場合がある
	foreach( $cpids as $cpid ) {
		$ret = posix_kill( $cpid, SIGTERM );	// sigterm
		if( $ret ) return false;				// 恐らくプロセスが存在するのでエラー
	}
	*/
	return true;	// 保証できない
}

////// ここから本編

// メッセージハンドラを得る
$ipc_key = ftok( RECORDER_CMD, "R" );
$msgh_r = msg_get_queue( $ipc_key );

$ipc_key = ftok( RECORDER_CMD, "W" );
$msgh_w = msg_get_queue( $ipc_key );

try{
	$rrec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
	$crec = new DBRecord( CHANNEL_TBL, "id" , $rrec->channel_id) ;
	
	// 時刻を得る
	$starttime = toTimestamp($rrec->starttime);
	$endtime   = toTimestamp($rrec->endtime);
	
	
	if( time() > $starttime ) {
		// 過去の録画予約
		$rrec->complete = 1;	// 終わったことにする
		throw new RecException("recorder:: なぜか過去の録画予約が実行された", EPGREC_ERROR );
	}
	reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画ジョブ開始" );
	
	// tuner
	$type_str = ($crec->type == "GR") ? "type = 'GR' " : "(type = 'BS' OR type = 'CS') ";
	$tuner = DBRecord::countRecords( RESERVE_TBL, "WHERE complete = '0' ".
												  "AND ".$type_str.
												  "AND id <> '".$rrec->id."' ".
												  "AND starttime < '".$rrec->endtime."' ".
												  "AND endtime > '".$rrec->starttime."'"
	);
	
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
	while( time() < $starttime ) {
		if( ($message = epgrec_get_message() ) != null ) {
			switch( $message ) {
				case "terminate":			// 終了指示
					epgrec_send_message("success");
					$rrec->complete = 1;	// 終わったことにする
					throw new RecException("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画が中断された" );
					break;
				
				case "stat":
					epgrec_send_message("alive");
					break;
					
				default:
					break;
			}
		}
		usleep( 50 * 1000 );				// 50ミリ秒待つ
	}
	
	// 録画開始
	
	$proch = false;
	if( ( $proch = epgrec_exec(DO_RECORD, $env_rec) ) !== false ) {
		reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画開始" );
		// 録画完了待ち
		$rec_cont = true;
		while( $rec_cont ){
			$st = proc_get_status($proch);
			if(! $st['running'] ) $rec_cont = false;    // 録画完了
			
			if( ($message = epgrec_get_message() ) != null ) {
				switch( $message ) {
					case "terminate":	// 終了指示
						if( epgrec_termproc( $proch ) == false ) {
							epgrec_send_message("error");
							reclog( "録画コマンドを停止できません", EPGREC_WARN );
						}
						else {
							epgrec_send_message("success");
							reclog("recorder:: 録画ID".$rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画が中断された" );
							$rec_cont = false;
						}
						break;
					
					case "stat":
						epgrec_send_message("alive");
						break;
				
					default:
						break;
				}
			}
			sleep(1);
		}
		proc_close( $proch );
		$proch = false;
	}
	else {
		$rrec->complete = 1;	// 終わったことにする
		throw new RecException("recorder:: 録画コマンドの実行に失敗した", EPGREC_ERROR );
	}
	
	// 予定より短いようなら終了時間を現在に書き換える
	if( time() < $endtime ) {
		$rrec->endtime = toDatetime( time() );
	}
	// 完了フラグを立てておく
	$rrec->complete = '1';
	
	// ちょっと待った方が確実っぽい
	sleep(15);
	@exec("sync");
	
	if( file_exists( INSTALL_PATH .$settings->spool . "/". $rrec->path ) ) {
		// 予約完了
		reclog( "recorder:: 予約ID". $rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画終了" );
	
		// サムネール作成
		if( $settings->use_thumbs == 1 ) {
			$gen_thumbnail = INSTALL_PATH."/gen-thumbnail.sh";
			if( defined("GEN_THUMBNAIL") ) 
				$gen_thumbnail = GEN_THUMBNAIL;
			@exec($gen_thumbnail);
		}
		
		if( $settings->mediatomb_update == 1 ) {
			$dbh = mysql_connect( $settings->db_host, $settings->db_user, $settings->db_pass );
			if( $dbh !== false ) {
				$sqlstr = "use ".$settings->db_name;
				@mysql_query( $sqlstr );
				// 別にやらなくてもいいが
				$sqlstr = "set NAME utf8";
				@mysql_query( $sqlstr );
				$sqlstr = "update mt_cds_object set metadata='dc:description=".mysql_real_escape_string($rrec->description)."&epgrec:id=".$reserve_id."' where dc_title='".$rrec->path."'";
				@mysql_query( $sqlstr );
				$sqlstr = "update mt_cds_object set dc_title='".mysql_real_escape_string($rrec->title)."(".date("Y/m/d").")' where dc_title='".$rrec->path."'";
				@mysql_query( $sqlstr );
			}
		}
	}
	else {	// 予約失敗
		reclog( "recomplete:: 予約ID". $rrec->id .":".$rrec->type.$rrec->channel.$rrec->title."の録画に失敗した模様", EPGREC_ERROR );
	}
}
catch( Exception $e ) {
	reclog( "recorder:: ".$e->getMessage(), EPGREC_ERROR );
}

msg_remove_queue( $msgh_r );	// メッセージハンドラ開放
msg_remove_queue( $msgh_w );	// メッセージハンドラ開放

// 省電力

if( intval($settings->use_power_reduce) != 0 ) {
	// 起動した理由を調べる
	if( file_exists(INSTALL_PATH. "/settings/wakeupvars.xml") ) {
		$wakeupvars_text = file_get_contents( INSTALL_PATH. "/settings/wakeupvars.xml" );
		$wakeupvars = new SimpleXMLElement($wakeupvars_text);
		if( strcasecmp( "reserve", $wakeupvars->reason ) == 0 ) {
			// 1時間以内に録画はないか？
			$count = DBRecord::countRecords( RESERVE_TBL, " WHERE complete <> '1' AND starttime < addtime( now(), '01:00:00') AND endtime > now()" );
			if( $count != 0 ) {	// 録画があるなら何もしない
				exit();
			}
			exec( $settings->shutdown . " -h +".$settings->wakeup_before );
		}
	}
}

?>
