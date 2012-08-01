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

  
  $settings = Settings::factory();
  
  if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );
  if( file_exists( $settings->temp_xml ) ) @unlink( $settings->temp_xml );

  // BSを処理する
  if( $settings->bs_tuners != 0 ) {
	// 録画重複チェック
	$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
	if( $num < $settings->bs_tuners ) {
	 	$cmdline = "CHANNEL=211 DURATION=180 TYPE=BS TUNER=0 MODE=0 OUTPUT=".$settings->temp_data." ".DO_RECORD . " >/dev/null 2>&1";
  		exec( $cmdline );
  		$cmdline = $settings->epgdump." /BS ".$settings->temp_data." ".$settings->temp_xml;
  		exec( $cmdline );
  		storeProgram( "BS", $settings->temp_xml );
  		if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );
  		if( file_exists( $settings->temp_xml ) ) @unlink( $settings->temp_xml );
	}

	// CS
	if ($settings->cs_rec_flg != 0) {
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if( $num < $settings->bs_tuners ) {
			$cmdline = "CHANNEL=CS8 DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$settings->temp_data." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = $settings->epgdump." /CS ".$settings->temp_data." ".$settings->temp_xml;
			exec( $cmdline );
			storeProgram( "CS", $settings->temp_xml );
			if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );
			if( file_exists( $settings->temp_xml ) ) @unlink( $settings->temp_xml );
		}
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if( $num < $settings->bs_tuners ) {
			$cmdline = "CHANNEL=CS24 DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$settings->temp_data." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = $settings->epgdump." /CS ".$settings->temp_data." ".$settings->temp_xml;
			exec( $cmdline );
			storeProgram( "CS", $settings->temp_xml );
			if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );
			if( file_exists( $settings->temp_xml ) ) @unlink( $settings->temp_xml );
	  	}
  	}
  }
  
  // 地上波を処理する
  if( $settings->gr_tuners != 0 ) {
	foreach( $GR_CHANNEL_MAP as $key=>$value ){
		// 録画重複チェック
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND type = 'GR' AND endtime > now() AND starttime < addtime( now(), '00:01:10')" );
		if( $num < $settings->gr_tuners ) {
			$cmdline = "CHANNEL=".$value." DURATION=60 TYPE=GR TUNER=0 MODE=0 OUTPUT=".$settings->temp_data." ".DO_RECORD . " >/dev/null 2>&1";
			exec( $cmdline );
			$cmdline = $settings->epgdump." ".$key." ".$settings->temp_data." ".$settings->temp_xml;
			exec( $cmdline );
			storeProgram( "GR", $settings->temp_xml );
 			if( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );
  			if( file_exists( $settings->temp_xml ) ) @unlink( $settings->temp_xml );
  		}
  	}
  }
  
  garbageClean();			//  不要プログラム削除
  doKeywordReservation();	// キーワード予約
  exit();
?>