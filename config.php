<?php

// settings/gr_channel.phpが作成された場合、
// config.php内の$GR_CHANNEL_MAPは無視されます

// 首都圏用地上デジタルチャンネルマップ
// 識別子 => チャンネル番号
$GR_CHANNEL_MAP = array(
	"GR27" => "27",		// NHK
	"GR26" => "26",		// 教育
	"GR25" => "25",		// 日テレ
	"GR22" => "22",		// 東京
	"GR21" => "21",		// フジ
	"GR24" => "24",		// テレ朝
	"GR23" => "23",		// テレ東
//	"GR20" => "20",		// MX TV
//	"GR18" => "18",		// テレ神
	"GR30" => "30",		// 千葉
//	"GR32" => "32",		// テレ玉
	"GR28" => "28",		// 大学
);

/*
// 大阪地区デジタルチャンネルマップ（参考）
$GR_CHANNEL_MAP = array(
	"GR24" => "24",		// NHK
	"GR13" => "13",		// 教育
	"GR16" => "16",		// 毎日
	"GR15" => "15",		// 朝日
	"GR17" => "17",		// 関西
	"GR14" => "14",		// 読売
	"GR18" => "18",		// テレビ大阪
);
*/


// 録画モード（option）

$RECORD_MODE = array(
	// ※ 0は必須で、変更不可です。
	0 => array(
		'name' => 'Full TS',	// モードの表示名
		'suffix' => '.ts',	// ファイル名のサフィックス
	),
	
	1 => array(
		'name' => 'Minimum TS',	// 最小のTS
		'suffix' => '_tss.ts',	// do-record.shのカスタマイズが必要
	),
	
	/* Example is as follows.
	2 => array(
		'name' => '12Mbps MPEG4',
		'suffix' => '.avi',
	),
	*/
);


//////////////////////////////////////////////////////////////////////////////
// 以降の変数・定数はほとんどの場合、変更する必要はありません


define( "INSTALL_PATH", dirname(__FILE__) );		// インストールパス

// ライブラリのディレクトリをinclude_pathに追加
$includes = array(INSTALL_PATH.'/classes', INSTALL_PATH.'/libs');
$incPath = implode(PATH_SEPARATOR, $includes);
set_include_path(get_include_path() . PATH_SEPARATOR . $incPath);
require_once 'Smarty/Smarty.class.php';
require_once 'epgrecLib.inc.php';
setlocale(LC_ALL, 'ja_JP.UTF-8');
spl_autoload_register('custom_autoloader');

// 以降は必要に応じて変更する

define( "PADDING_TIME",  180 );											// 詰め物時間
define( "DO_RECORD",     INSTALL_PATH . "/scripts/do-record.sh" );		// レコードスクリプト
define( "GEN_THUMBNAIL", INSTALL_PATH . "/scripts/gen-thumbnail.sh" );	// サムネール生成スクリプト
define( "GET_EPG_CMD",   INSTALL_PATH . "/scripts/getEpg.php" );		// EPGデータ取得コマンド
define( "STORE_PRG_CMD", INSTALL_PATH . "/scripts/storeProgram.php" );	// 番組データ保存コマンド
define( "RECORDER_CMD",  INSTALL_PATH . "/scripts/recorder.php" );		// 録画制御コマンド
define( "COMPLETE_CMD",  INSTALL_PATH . "/scripts/recomplete.php" );	// 録画終了コマンド

// BS/CSでEPGを取得するチャンネル
// 通常は変える必要はありません
// BSでepgdumpが頻繁に落ちる場合は、受信状態のいいチャンネルに変えることで
// 改善するかもしれません

define( "BS_EPG_CHANNEL",  "BS09_0" );	// BS
define( "CS1_EPG_CHANNEL", "CS8"    );	// CS1
define( "CS2_EPG_CHANNEL", "CS24"   );	// CS2

// 地上デジタルチャンネルテーブルsettings/gr_channel.phpが存在するならそれを
// 優先する
if ( file_exists( INSTALL_PATH."/settings/gr_channel.php" ) )
{
	unset($GR_CHANNEL_MAP);
	include_once( INSTALL_PATH."/settings/gr_channel.php" );
}

//
// settings/site_conf.phpがあればそれを優先する
//
if ( file_exists( INSTALL_PATH."/settings/site_conf.php" ) )
{
	unset($GR_CHANNEL_MAP);
	unset($RECORD_MODE);
	include_once( INSTALL_PATH."/settings/site_conf.php" );
}

// Deprecated
// カスタマイズした設定をロードし、デフォルト設定をオーバライドする
// unsetはカスタム設定ファイルの責任で行う
if ( file_exists( INSTALL_PATH."/settings/config_custom.php" ) )
{
	include_once( INSTALL_PATH."/settings/config_custom.php" );
}

// DBテーブル情報　以下は変更しないでください

define( "RESERVE_TBL",  "reserveTbl" );						// 予約テーブル
define( "PROGRAM_TBL",  "programTbl" );						// 番組表
define( "CHANNEL_TBL",  "channelTbl" );						// チャンネルテーブル
define( "CATEGORY_TBL", "categoryTbl" );					// カテゴリテーブル
define( "KEYWORD_TBL",  "keywordTbl" );						// キーワードテーブル
define( "LOG_TBL",      "logTbl" );							// ログテーブル
?>
