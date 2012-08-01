#!/usr/bin/php
<?php
  $script_path = dirname( __FILE__ );
  chdir( $script_path );
  include_once( $script_path . '/config.php');

  $type = $argv[1];	// BS CS1 CS2 GR
  $file = $argv[2];	// TSファイル
  $key  = "";
  
  // プライオリティ低に
  pcntl_setpriority(20);
  
  include_once( INSTALL_PATH . '/DBRecord.class.php' );
  include_once( INSTALL_PATH . '/Reservation.class.php' );
  include_once( INSTALL_PATH . '/Keyword.class.php' );
  include_once( INSTALL_PATH . '/Settings.class.php' );
  include_once( INSTALL_PATH . '/storeProgram.inc.php' );
  include_once( INSTALL_PATH . "/reclib.php" );
  
  
  $settings = Settings::factory();
  
  $xmlfile = "";
  $cmdline = $settings->epgdump." ";
  
  if( $type === "GR" ) {
	$key = $argv[3];	// key
	$xmlfile = $settings->temp_xml.$key."";
	$cmdline .= $key." ".$file." ".$xmlfile;
  }
  else if( $type === "CS1" ) {
	$type = "CS";
	$xmlfile = $settings->temp_xml."_cs1";
	$cmdline .= "/CS ".$file." ".$xmlfile;
  }
  else if( $type === "CS2" ) {
	$type = "CS";
	$xmlfile = $settings->temp_xml."_cs2";
	$cmdline .= "/CS ".$file." ".$xmlfile;
  }
  else if( $type === "BS" ) {
	$xmlfile = $settings->temp_xml."_bs";
	$cmdline .= "/BS ".$file." ".$xmlfile;
  }
  else exit();
  
  exec( $cmdline );
  if( file_exists( $xmlfile ) ) {
	storeProgram( $type, $xmlfile );
	@unlink( $xmlfile );
  }
  else {
	reclog( "storeProgram:: 正常な".$xmlfile."が作成されなかった模様(放送間帯でないなら問題ありません)", EPGREC_WARN );
  }
  
  if( file_exists( $file ) ) @unlink( $file );

  
  /*
  garbageClean();			//  不要プログラム削除
  doKeywordReservation();	// キーワード予約
  */
?>