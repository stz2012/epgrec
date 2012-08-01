#!/usr/bin/php
<?php
  $script_path = dirname( __FILE__ );
  chdir( $script_path );
  include_once( $script_path . '/config.php');
  include_once( INSTALL_PATH . '/DBRecord.class.php' );
  include_once( INSTALL_PATH . '/Reservation.class.php' );
  include_once( INSTALL_PATH . '/Keyword.class.php' );
  include_once( INSTALL_PATH . '/Settings.class.php' );
  include_once( INSTALL_PATH . '/storeProgram.inc.php' );
  include_once( INSTALL_PATH . '/recLog.inc.php' );
  
  
  // SIGTERMシグナル
  function handler( $signo = 0 ) {
	// とりあえずシグナルは無視する
  }
  
  // デーモン化
  function daemon() {
  	if( pcntl_fork() != 0 )
 		exit();
	posix_setsid();
	if( pcntl_fork() != 0 )
		exit;
	pcntl_signal(SIGTERM, "handler");
	
	fclose(STDIN);
	fclose(STDOUT);
	fclose(STDERR);
  }
  
  function check_file( $file ) {
	// ファイルがないなら無問題
	if( ! file_exists( $file ) ) return true;

	// 1時間以上前のファイルなら削除してやり直す
	if( (time() - filemtime( $file )) > 3600 ) {
		@unlink( $file );
		return true;
	}

	return false;
  }
  
  // 子プロセス起動
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
  
  daemon();
  
  $settings = Settings::factory();
  
  // ユーザー/グループの切り替えを試みる
  if(intval($settings->use_power_reduce) != 0 ) {
	$userinfo = posix_getpwnam( $settings->www_user );
	$groupinfp = posix_getgrnam( $settings->www_group );
	
	posix_setgid( $groupinfo['gid'] );
	posix_setuid( $userinfo['uid'] );
  }
  
  
  // 後方互換性
  if( ! defined( "BS_EPG_CHANNEL" )  ) define( "BS_EPG_CHANNEL",  "211"  );
  if( ! defined( "CS1_EPG_CHANNEL" ) ) define( "CS1_EPG_CHANNEL", "CS8"  );
  if( ! defined( "CS2_EPG_CHANNEL" ) ) define( "CS2_EPG_CHANNEL", "CS24" );
  
  $bs_proc = false;
  $gr_procs = array();
  $cs1_proc = false;
  $cs2_proc = false;
  

  $temp_data_bs  = $settings->temp_data.".bs";
  $temp_data_cs1 = $settings->temp_data.".cs1";
  $temp_data_cs2 = $settings->temp_data.".cs2";
  $temp_data_gr  = $settings->temp_data.".gr";

  if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );

  // BSを処理する
  if( $settings->bs_tuners != 0 ) {
	// 録画重複チェック
	$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
	if($num < $settings->bs_tuners && check_file($temp_data_bs)) {
	 	$cmdline = "CHANNEL=".BS_EPG_CHANNEL." DURATION=180 TYPE=BS TUNER=0 MODE=0 OUTPUT=".$temp_data_bs." ".DO_RECORD . " >/dev/null 2>&1";
  		exec( $cmdline );
  		
		$cmdline = INSTALL_PATH."/storeProgram.php BS ".$temp_data_bs;
		$bs_proc = epgrec_exec( $cmdline );
	}
	
	// CS
	if ($settings->cs_rec_flg != 0) {
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if($num < $settings->bs_tuners && check_file($temp_data_cs1)) {
		
			$cmdline = "CHANNEL=".CS1_EPG_CHANNEL." DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$temp_data_cs1." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = INSTALL_PATH."/storeProgram.php CS1 ".$temp_data_cs1;
			$cs1_proc = epgrec_exec($cmdline);
		}
		
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if( ($num < $settings->bs_tuners) && check_file($temp_data_cs2) ) {
			$cmdline = "CHANNEL=".CS2_EPG_CHANNEL." DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$temp_data_cs2." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = INSTALL_PATH."/storeProgram.php CS2 ".$temp_data_cs2;
			$cs2_proc = epgrec_exec( $cmdline );
	  	}
  	}
  }

  // 地上波を処理する
  if( $settings->gr_tuners != 0 ) {
	foreach( $GR_CHANNEL_MAP as $key=>$value ){
		// 録画重複チェック
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND type = 'GR' AND endtime > now() AND starttime < addtime( now(), '00:01:10')" );
		if($num < $settings->gr_tuners && check_file($temp_data_gr.$value."")) {
			$cmdline = "CHANNEL=".$value." DURATION=60 TYPE=GR TUNER=0 MODE=0 OUTPUT=".$temp_data_gr.$value." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = INSTALL_PATH."/storeProgram.php GR ".$temp_data_gr.$value." ".$key;
			$gr_procs[] = epgrec_exec( $cmdline );
  		}
  	}
  }
  
  // 終了を待つ
  // 一時的にdefunctするがまあいいや
  $counter = 0;
  do {
	sleep(1);
	
	$counter = 0;
	if( count($gr_procs) != 0 ) {
		foreach( $gr_procs as $proc ) {
			$status = proc_get_status( $proc );
			if( $status['running'] ) $counter++;
		}
	}
	
	if( $bs_proc !== false ){
		$status = proc_get_status($bs_proc);
		if( $status['running'] ) $counter++;
	}
  	
	if( $cs1_proc !== false ){
		$status = proc_get_status($bs_proc);
		if( $status['running'] ) $counter++;
	}
	
	if( $cs2_proc !== false ){
		$status = proc_get_status($bs_proc);
		if( $status['running'] ) $counter++;
	}
	
  } while( $counter != 0 );
  
  
  garbageClean();			//  不要プログラム削除
  doKeywordReservation();	// キーワード予約
  
  if( intval($settings->use_power_reduce) != 0 ) {
	if( file_exists(INSTALL_PATH. "/settings/wakeupvars.xml") ) {
		$wakeupvars_text = file_get_contents( INSTALL_PATH. "/settings/wakeupvars.xml" );
		$wakeupvars = new SimpleXMLElement($wakeupvars_text);

		// getepg終了時を書込み
		$wakeupvars->getepg_time = time();
		// 起動理由を調べる
		if( strcasecmp( "getepg", $wakeupvars->reason ) == 0 ) {
			// 1時間以内に録画はないか？
			$count = DBRecord::countRecords( RESERVE_TBL, " WHERE complete <> '1' AND starttime < addtime( now(), '01:00:00') AND endtime > now()" );
			if( $count != 0 ) {	// 録画があるなら録画起動にして終了
				$wakeupvars->reason = "reserve";
			}
			else {
				exec( $settings->shutdown . " -h +".$settings->wakeup_before );
			}
		}
		$wakeupvars->asXML(INSTALL_PATH. "/settings/wakeupvars.xml");
	}
  }
  exit();
?>
