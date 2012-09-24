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
	
	// RESERVE_TBL

	$sqlstrs = array (
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  channel_disc varchar(128) not null default 'none';",	// channel disc
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  channel_id integer not null  default '0';",			// channel ID
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  program_id integer not null default '0';",				// Program ID
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  type varchar(8) not null default 'GR';",				// 種別（GR/BS/CS）
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  channel varchar(10) not null default '0';",			// チャンネル
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  title varchar(512) not null default 'none';",			// タイトル
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  description varchar(512) not null default 'none';",		// 説明 text->varchar
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  category_id integer not null default '0';",			// カテゴリID
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  starttime datetime not null default '1970-01-01 00:00:00';",	// 開始時刻
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  endtime datetime not null default '1970-01-01 00:00:00';",		// 終了時刻
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  job integer not null default '0';",					// job番号
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  path blob default null;",								// 録画ファイルパス
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  complete boolean not null default '0';",				// 完了フラグ
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  reserve_disc varchar(128) not null default 'none';",	// 識別用hash
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  autorec integer not null default '0';",				// キーワードID
	 "alter table ".$settings->tbl_prefix.RESERVE_TBL." modify  mode integer not null default '0';",					//録画モード
	);
	
	if( column_exists( $settings->tbl_prefix.RESERVE_TBL, "dirty", $dbh) ) {
		echo "dirtyフィールドはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "alter table ".$settings->tbl_prefix.RESERVE_TBL." add dirty boolean not null default '0';" );
	}
	
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "予約テーブルのアップデートに失敗\n";
	}
	
	// インデックス追加
	$sqlstrs = array();
	if( index_exists( $settings->tbl_prefix.RESERVE_TBL, "reserve_ch_idx", $dbh ) ) {
		echo "reserve_ch_idxはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "create index reserve_ch_idx on ".$settings->tbl_prefix.RESERVE_TBL."  (channel_disc);" );
	}
	if( index_exists( $settings->tbl_prefix.RESERVE_TBL, "reserve_st_idx", $dbh ) ) {
		echo "reserve_st_idxはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "create index reserve_st_idx on ".$settings->tbl_prefix.RESERVE_TBL."  (starttime);" );
	}
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "予約テーブルにインデックスが作成できません\n";
	}
	
	// PROGRAM_TBL
	
	$sqlstrs = array (
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify channel_disc varchar(128) not null default 'none';",	// channel disc
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify channel_id integer not null default '0';",				// channel ID
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify type varchar(8) not null default 'GR';",				// 種別（GR/BS/CS）
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify channel varchar(10) not null default '0';",			// チャンネル
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify title varchar(512) not null default 'none';",			// タイトル
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify description varchar(512) not null default 'none';",	// 説明 text->varchar
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify category_id integer not null default '0';",			// カテゴリID
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify starttime datetime not null default '1970-01-01 00:00:00';",	// 開始時刻
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify endtime datetime not null default '1970-01-01 00:00:00';",		// 終了時刻
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify program_disc varchar(128) not null default 'none';",	 		// 識別用hash
		"alter table ".$settings->tbl_prefix.PROGRAM_TBL." modify autorec boolean not null default '1';",					// 自動録画有効無効
	);
	
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "番組テーブルのアップデートに失敗\n";
	}
	
	// インデックス追加
	$sqlstrs = array();
	if( index_exists( $settings->tbl_prefix.PROGRAM_TBL , "program_ch_idx", $dbh ) ) {
		echo "program_ch_idxはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "create index program_ch_idx on ".$settings->tbl_prefix.PROGRAM_TBL." (channel_disc);" );
	}
	if( index_exists( $settings->tbl_prefix.PROGRAM_TBL , "program_st_idx", $dbh ) ) {
		echo "program_st_idxはすでに存在しているため作成しません\n";
	}
	else {
		array_push( $sqlstrs, "create index program_st_idx on ".$settings->tbl_prefix.PROGRAM_TBL." (starttime);" );
	}
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "番組テーブルにインデックスが作成できません\n";
	}
	
	// CHANNEL_TBL
	
	$sqlstrs = array(
		"alter table ".$settings->tbl_prefix.CHANNEL_TBL." modify type varchar(8) not null default 'GR';",				// 種別
		"alter table ".$settings->tbl_prefix.CHANNEL_TBL." modify channel varchar(10) not null default '0';",			// channel
		"alter table ".$settings->tbl_prefix.CHANNEL_TBL." modify name varchar(512) not null default 'none';",			// 表示名
		"alter table ".$settings->tbl_prefix.CHANNEL_TBL." modify channel_disc varchar(128) not null default 'none';",	// 識別用hash
	);
	if( column_exists( $settings->tbl_prefix.CHANNEL_TBL, "sid", $dbh ) ) {
		echo "sidフィールドは存在しているので作成しません\n";
	}
	else {
		array_push( $sqlstrs , "alter table ".$settings->tbl_prefix.CHANNEL_TBL." add sid varchar(64) not null default 'hd'" );
	}
	if( column_exists( $settings->tbl_prefix.CHANNEL_TBL, "skip", $dbh ) ) {
		echo "skipフィールドは存在しているので作成しません\n";
	}
	else {
		array_push( $sqlstrs , "alter table ".$settings->tbl_prefix.CHANNEL_TBL." add skip boolean not null default '0'" );
	}
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "チャンネルテーブルのアップデートに失敗\n";
	}
	
	// CATEGORY_TBL
	
	$sqlstrs  = array(
		"alter table ".$settings->tbl_prefix.CATEGORY_TBL." modify name_jp varchar(512) not null default 'none';",		// 表示名
		"alter table ".$settings->tbl_prefix.CATEGORY_TBL." modify name_en varchar(512) not null default 'none';",		// 同上
		"alter table ".$settings->tbl_prefix.CATEGORY_TBL." modify category_disc varchar(128) not null default 'none'",	// 識別用hash
	);
	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "カテゴリテーブルのアップデートに失敗\n";
	}
	
	// KEYWORD_TBL
	
	$sqlstrs = array(
		"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify keyword varchar(512) not null default '*';",			// 表示名
		"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify type varchar(8) not null default '*';",				// 種別
		"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify channel_id integer not null default '0';",				// channel ID
		"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify category_id integer not null default '0';",			// カテゴリID
		"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify use_regexp boolean not null default '0';",				// 正規表現を使用するなら1
	 );
	if( column_exists( $settings->tbl_prefix.KEYWORD_TBL, "autorec_mode", $dbh ) ) {
		echo "autorec_modeは存在しているので作成しません\n";
	}
	else {
		array_push( $sqlstrs, "alter table ".$settings->tbl_prefix.KEYWORD_TBL." add autorec_mode integer not null default '0';");
	}
	if( column_exists( $settings->tbl_prefix.KEYWORD_TBL, "weekofday", $dbh ) ) {
		echo "weekofdayは存在しているので作成しません\n";
		array_push( $sqlstrs, "alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify weekofday enum ('0','1','2','3','4','5','6','7' ) not null default '7'" );
	}
	else {
		array_push( $sqlstrs, "alter table ".$settings->tbl_prefix.KEYWORD_TBL." add weekofday enum ('0','1','2','3','4','5','6','7' ) not null default '7'" );
	}
	if( column_exists( $settings->tbl_prefix.KEYWORD_TBL, "prgtime", $dbh ) ) {
		echo "prgtimeは存在しているので作成しません\n";
		array_push( $sqlstrs, 
			"alter table ".$settings->tbl_prefix.KEYWORD_TBL." modify prgtime enum ('0','1','2','3','4','5','6','7','8','9','10','11','12',".
																				"'13','14','15','16','17','18','19','20','21','22','23','24') not null default '24'" );
	}
	else {
		array_push( $sqlstrs, 
			"alter table ".$settings->tbl_prefix.KEYWORD_TBL." add prgtime enum ('0','1','2','3','4','5','6','7','8','9','10','11','12',".
																				"'13','14','15','16','17','18','19','20','21','22','23','24') not null default '24'" );
	}

	if( multi_query( $sqlstrs, $dbh ) ) {
		echo "キーワードテーブルのアップデートに失敗\n";
	}

	// ログテーブル新規作成

	try {
		$log = new DBRecord( LOG_TBL );
		$log->createTable( LOG_STRUCT );
	}
	catch( Exception $e ) {
		echo $e->getMessage();
		echo "\n";
	}

}
else
	exit( "DBの接続に失敗\n" );
?>
