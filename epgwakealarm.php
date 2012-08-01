#!/usr/bin/php
<?php
  $script_path = dirname( __FILE__ );
  chdir( $script_path );
  include_once( $script_path . '/config.php');
  include_once( INSTALL_PATH . '/DBRecord.class.php' );
  include_once( INSTALL_PATH . '/Reservation.class.php' );
  include_once( INSTALL_PATH . '/Settings.class.php' );
  include_once( INSTALL_PATH . '/recLog.inc.php' );
  
  $acpi_timer_path = "/sys/class/rtc/rtc0/wakealarm";	// ここは書き換える必要があるPCがあるかもしれない
  
  $settings = Settings::factory();
  
  $wakeupvars_text = '<?xml version="1.0" encoding="UTF-8" ?><epgwakeup></epgwakeup>';
  if( file_exists( INSTALL_PATH. "/settings/wakeupvars.xml" ) )
	$wakeupvars_text = file_get_contents( INSTALL_PATH. "/settings/wakeupvars.xml" );
  
  $wakeupvars = new SimpleXMLElement($wakeupvars_text);
  if(count($wakeupvars->getepg_time) == 0) {
	 $wakeupvars->getepg_time = 0;
  }
  
  if( strcasecmp( $argv[1], "start" ) == 0 ) {
	try {
		// 規定時間以内に予約はあるか
		$recstart_time = "00:".sprintf("%02d", intval($settings->wakeup_before) + 5).":00";
		$count = DBRecord::countRecords( RESERVE_TBL, " WHERE complete <> '1' AND starttime > now() AND starttime <= addtime(now(), '".$recstart_time."')" );
		if( $count > 0 ) {
			$wakeupvars->reason = "reserve";
		}
		else if( (intval($wakeupvars->getepg_time) + intval($settings->getepg_timer) * 3600 ) <= time() ) {
			$wakeupvars->reason = "getepg";
			exec( INSTALL_PATH."/getepg.php >/dev/null 2>&1" );
		}
		else {
			$wakeupvars->reason = "other";
		}
		$wakeupvars->asXML(INSTALL_PATH. "/settings/wakeupvars.xml");
		chmod(INSTALL_PATH. "/settings/wakeupvars.xml", 0666 );
	}
	catch( Exception $e ) {
		//
	}
	exit();
  }
  else if( strcasecmp( $argv[1], "stop" ) == 0 ) {
	try {
		// 録画中はないか？
		$count = DBRecord::countRecords( RESERVE_TBL, " WHERE complete <> '1' AND starttime < now() AND endtime > now()" );
		if( $count != 0 ) {
			// シャットダウン中止を試みる
			exec( $settings->shutdown." -c" );
			recLog("予約中にシャットダウンが実行された", EPGREC_WARN );
			exit();
		}
		
		$waketime = 0;
		// 次の予約録画の開始時刻は？
		$nextreserves = DBRecord::createRecords( RESERVE_TBL, " WHERE complete <> '1' ORDER BY starttime LIMIT 10" );
		$next_rectime = 0;
		foreach( $nextreserves as $reserve ) {
			$next_rectime = toTimestamp($reserve->starttime);
			if( $next_rectime > time() ) break;								// 現在より未来であるか？
		}
		$next_rectime = $next_rectime - 60 * intval($settings->wakeup_before);
		if( $next_rectime < time() ) {
			// シャットダウン中止を試みる
			exec( $settings->shutdown." -c" );
			recLog("予約録画開始".$settings->wakeup_before."分以内にシャットダウンが実行された", EPGREC_WARN );
			exit();
		}
		
		// 次のgetepgの時間は？
		$next_getepg_time = 0;
		if( intval($wakeupvars->getepg_time) == 0 ) {
			$next_getepg_time = time() + intval($settings->getepg_timer) * 3600;	// 現在から設定時間後
		}
		else {
			$next_getepg_time = intval($wakeupvars->getepg_time) + intval($settings->getepg_timer) * 3600;
			if( $next_getepg_time < time() ) {
				$next_getepg_time = time() + intval($settings->getepg_timer) * 3600;
			}
		}
		
		if( $next_getepg_time < $next_rectime ) 
			$waketime = $next_getepg_time;
		else
			$waketime = $next_rectime;

		// いったんリセットする
		$fp = fopen( $acpi_timer_path, "w" );
		fwrite($fp , "0");
		fclose($fp);
		
		$fp = fopen( $acpi_timer_path, "w" );
		fwrite($fp , "".$waketime );
		fclose($fp);
 	}
 	catch( Exception $e ) {
		//
 	}
 	exit();
 }
?>
