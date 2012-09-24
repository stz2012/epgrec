#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once($script_path . '/config.php');
include_once(INSTALL_PATH . '/Settings.class.php' );
include_once(INSTALL_PATH . '/DBRecord.class.php' );
include_once(INSTALL_PATH . '/tableStruct.inc.php' );

// mysqli::multi_queryは動作がいまいちなので使わない

function multi_query( $sqlstrs, $dbh ) {
	$error = false;
	
	foreach( $sqlstrs as $sqlstr ) {
		$res = mysql_query( $sqlstr );
		if( $res === FALSE ) {
			echo "failed: ". $sqlstr . "\n";
			$error = true;
		}
	}
	return $error;
}

function column_exists( $tbl, $col, $dbh ) {
	$sqlstr = "show fields from ".$tbl." where Field='".$col."'";
	$res = mysql_query( $sqlstr, $dbh );
	return mysql_num_rows($res);
}

function index_exists( $tbl, $idx, $dbh ) {
	$sqlstr = "show index from ".$tbl." where Key_name='".$idx."'";
	$res = mysql_query( $sqlstr, $dbh );
	return mysql_num_rows($res);
}


$settings = Settings::factory();
$dbh = mysql_connect( $settings->db_host, $settings->db_user, $settings->db_pass );
if( $dbh !== FALSE ) {

	$sqlstr = "use ".$settings->db_name;
	mysql_query( $sqlstr );

	$sqlstr = "set NAMES 'utf8'";
	mysql_query( $sqlstr );
	
	// PROGRAM_TBL

	// インデックス追加
	$sqlstrs = array();
	if( index_exists( $settings->tbl_prefix.PROGRAM_TBL, "program_disc_idx", $dbh ) ) {
		echo "program_disc_idxはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "create index program_disc_idx on ".$settings->tbl_prefix.PROGRAM_TBL."  (program_disc);" );
	}
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "予約テーブルにインデックスが作成できません\n";
	}
}
else
	exit( "DBの接続に失敗\n" );
?>
