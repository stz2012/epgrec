<?php
include_once('config.php');
include_once( INSTALL_PATH . "/DBRecord.class.php" );
include_once( INSTALL_PATH . "/Settings.class.php" );


if( isset($_POST['sid']) && isset($_POST['channel_disc']) && isset($_POST['skip']) ) {
	
	try {
		$crec = new DBRecord( CHANNEL_TBL, "channel_disc", $_POST['channel_disc'] );
		$crec->sid = trim($_POST['sid']);
		$crec->skip = (int)(trim($_POST['skip']));
	}
	catch( Exception $e ) {
		exit("Error: チャンネル情報更新失敗" );
	}
}
?>