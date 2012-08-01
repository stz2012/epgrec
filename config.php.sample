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


// BSチューナーとして黒Friioを用いているのなら下のfalseをtrueに変えてください。

define( "USE_KUROBON", false );



//////////////////////////////////////////////////////////////////////////////
// 以降の変数・定数はほとんどの場合、変更する必要はありません


define( "INSTALL_PATH", dirname(__FILE__) );		// インストールパス

// 以降は必要に応じて変更する

define( "PADDING_TIME", 180 );						// 詰め物時間
define( "DO_RECORD", INSTALL_PATH . "/do-record.sh" );		// レコードスクリプト
define( "COMPLETE_CMD", INSTALL_PATH . "/recomplete.php" );	// 録画終了コマンド
define( "GEN_THUMBNAIL", INSTALL_PATH . "/gen-thumbnail.sh" );	// サムネール生成スクリプト
define( "RECORDER_CMD", INSTALL_PATH . "/recorder.php" );

// BS/CSでEPGを取得するチャンネル
// 通常は変える必要はありません
// BSでepgdumpが頻繁に落ちる場合は、受信状態のいいチャンネルに変えることで
// 改善するかもしれません

define( "BS_EPG_CHANNEL",  "211"  );	// BS
define( "CS1_EPG_CHANNEL", "CS8"  );	// CS1
define( "CS2_EPG_CHANNEL", "CS24" );	// CS2

// 全国用BSデジタルチャンネルマップ
$BS_CHANNEL_MAP = array(
	"4101.epgdata.ontvjapan" => "101",
	"4103.epgdata.ontvjapan" => "103",
	"4141.epgdata.ontvjapan" => "141",
	"4151.epgdata.ontvjapan" => "151",
	"4161.epgdata.ontvjapan" => "161",
	"4171.epgdata.ontvjapan" => "171",
	"4181.epgdata.ontvjapan" => "181",
	"4191.epgdata.ontvjapan" => "191",
	"4192.epgdata.ontvjapan" => "192",
	"4193.epgdata.ontvjapan" => "193",
	"4200.epgdata.ontvjapan" => "200",
	"4201.epgdata.ontvjapan" => "201",
	"4202.epgdata.ontvjapan" => "202",
	"4211.epgdata.ontvjapan" => "211",
	"4222.epgdata.ontvjapan" => "222",
	"4231.epgdata.ontvjapan" => "231",
	"4232.epgdata.ontvjapan" => "232",
	"4233.epgdata.ontvjapan" => "233",
	"4234.epgdata.ontvjapan" => "234",
	"4236.epgdata.ontvjapan" => "236",
	"4238.epgdata.ontvjapan" => "238",
	"4241.epgdata.ontvjapan" => "241",
	"4242.epgdata.ontvjapan" => "242",
	"4243.epgdata.ontvjapan" => "243",
	"4244.epgdata.ontvjapan" => "244",
	"4245.epgdata.ontvjapan" => "245",
	"4251.epgdata.ontvjapan" => "251",
	"4252.epgdata.ontvjapan" => "252",
	"4255.epgdata.ontvjapan" => "255",
	"4256.epgdata.ontvjapan" => "256",
	"4258.epgdata.ontvjapan" => "258",
	"4291.epgdata.ontvjapan" => "291",
	"4292.epgdata.ontvjapan" => "292",
	"4294.epgdata.ontvjapan" => "294",
	"4295.epgdata.ontvjapan" => "295",
	"4296.epgdata.ontvjapan" => "296",
	"4297.epgdata.ontvjapan" => "297",
	"4298.epgdata.ontvjapan" => "298",
	"4531.epgdata.ontvjapan" => "531",
//	"4910.epgdata.ontvjapan" => "910",
);

if( USE_KUROBON ) {
	$BS_CHANNEL_MAP = array(
		"4101.epgdata.ontvjapan" => "B18",
		"4103.epgdata.ontvjapan" => "B19",
		"4141.epgdata.ontvjapan" => "B16",
		"4151.epgdata.ontvjapan" => "B1",
		"4161.epgdata.ontvjapan" => "B2",
		"4171.epgdata.ontvjapan" => "B3",
		"4181.epgdata.ontvjapan" => "B17",
		"4191.epgdata.ontvjapan" => "B4",
		"4192.epgdata.ontvjapan" => "B5",
		"4193.epgdata.ontvjapan" => "B6",
		"4200.epgdata.ontvjapan" => "B11",
		"4201.epgdata.ontvjapan" => "B7",
		"4202.epgdata.ontvjapan" => "B7",
		"4211.epgdata.ontvjapan" => "B10",
		"4222.epgdata.ontvjapan" => "B12",
		"4231.epgdata.ontvjapan" => "B15",
		"4232.epgdata.ontvjapan" => "B15",
		"4233.epgdata.ontvjapan" => "B15",
//		"4234.epgdata.ontvjapan" => "234",
		"4236.epgdata.ontvjapan" => "B8",
		"4238.epgdata.ontvjapan" => "B13",
		"4241.epgdata.ontvjapan" => "B22",
		"4242.epgdata.ontvjapan" => "B23",
		"4243.epgdata.ontvjapan" => "B23",
		"4244.epgdata.ontvjapan" => "B25",
		"4245.epgdata.ontvjapan" => "B26",
		"4251.epgdata.ontvjapan" => "B27",
		"4252.epgdata.ontvjapan" => "B24",
		"4255.epgdata.ontvjapan" => "B28",
		"4256.epgdata.ontvjapan" => "B9",
		"4258.epgdata.ontvjapan" => "B29",
		"4291.epgdata.ontvjapan" => "B20",
		"4292.epgdata.ontvjapan" => "B20",
		"4294.epgdata.ontvjapan" => "B21",
		"4295.epgdata.ontvjapan" => "B21",
		"4296.epgdata.ontvjapan" => "B21",
		"4297.epgdata.ontvjapan" => "B21",
		"4298.epgdata.ontvjapan" => "B20",
		"4531.epgdata.ontvjapan" => "B15",
		"4910.epgdata.ontvjapan" => "B19",
	);
}

// 全国用CSデジタルチャンネルマップ
$CS_CHANNEL_MAP = array(
	"1002.ontvjapan.com"		=>	"CS2", // 237,	// "スターｃｈプラス"
	"1086.ontvjapan.com"		=>	"CS2", // 239,	// "日本映画専門ｃｈＨＤ"
	"306ch.epgdata.ontvjapan"	=>	"CS2", // 306,	// "フジテレビＮＥＸＴ"

	"100ch.epgdata.ontvjapan"	=>	"CS4", // 100,	// "ｅ２プロモ"
	"1025.ontvjapan.com"		=>	"CS4", // 256,	// "Ｊスポーツ　ＥＳＰＮ"
	"1016.ontvjapan.com"		=>	"CS4", // 312,	// "ＦＯＸ"
	"1018.ontvjapan.com"		=>	"CS4", // 322,	// "スペースシャワーＴＶ"
	"1046.ontvjapan.com"		=>	"CS4", // 331,	// "カートゥーン　ネット"
	"294ch.epgdata.ontvjapan"	=>	"CS4", // 294,	// "ホームドラマch"
	"1213.ontvjapan.com"		=>	"CS4", // 334,	// "トゥーン・ディズニー"

	"1010.ontvjapan.com"		=>	"CS6", // 221,	// "東映チャンネル"
	"1005.ontvjapan.com"		=>	"CS6", // 222,	// "衛星劇場"
	"1008.ontvjapan.com"		=>	"CS6", // 223,	// "チャンネルＮＥＣＯ"
	"1009.ontvjapan.com"		=>	"CS6", // 224,	// "洋画★シネフィル"
	"1133.ontvjapan.com"		=>	"CS6", // 292,	// "時代劇専門チャンネル"
	"1003.ontvjapan.com"		=>	"CS6", // 238,	// "スター・クラシック"
	"1006.ontvjapan.com"		=>	"CS6", // 310,	// "スーパードラマ"
	"1014.ontvjapan.com"		=>	"CS6", // 311,	// "ＡＸＮ"
	"1204.ontvjapan.com"		=>	"CS6", // 343,	// "ナショジオチャンネル"

	"1059.ontvjapan.com"		=>	"CS8", // 55,	//  "ショップチャンネル"
	"1045.ontvjapan.com"		=>	"CS8", // 335,	// "キッズステーションＨＤ"

	"1217.ontvjapan.com"		=>	"CS10", // 228,	// "ザ・シネマ"
	"800ch.epgdata.ontvjapan"	=>	"CS10", // 800,	// "スカチャンＨＤ８００"
	"801ch.epgdata.ontvjapan"	=>	"CS10", // 801,	// "スカチャン８０１"
	"802ch.epgdata.ontvjapan"	=>	"CS10", // 802,	// "スカチャン８０２"

	"1028.ontvjapan.com"		=>	"CS12", // 260,	// "ゴルフチャンネル"
	"1092.ontvjapan.com"		=>	"CS12", // 303,	// "テレ朝チャンネル"
	"1019.ontvjapan.com"		=>	"CS12", // 323,	// "ＭＴＶ"
	"1024.ontvjapan.com"		=>	"CS12", // 324,	// "ミュージック・エア"
	"1067.ontvjapan.com"		=>	"CS12", // 352,	// "朝日ニュースター"
	"1070.ontvjapan.com"		=>	"CS12", // 353,	// "ＢＢＣワールド"
	"1069.ontvjapan.com"		=>	"CS12", // 354,	// "ＣＮＮｊ"
	"361ch.epgdata.ontvjapan"	=>	"CS12", // 361,	// "ジャスト・アイ"

	"1041.ontvjapan.com"		=>	"CS14", // 251,	// "Ｊスポーツ　１"
	"1042.ontvjapan.com"		=>	"CS14", // 252,	// "Ｊスポーツ　２"
	"1043.ontvjapan.com"		=>	"CS14", // 253,	// "ＪスポーツＰｌｕｓＨ"
	"1026.ontvjapan.com"		=>	"CS14", // 254,	// "ＧＡＯＲＡ"
	"1040.ontvjapan.com"		=>	"CS14", // 255,	// "ｓｋｙ・Ａスポーツ＋"

	"305ch.epgdata.ontvjapan"	=>	"CS16", // 305,	// "チャンネル銀河"
	"1201.ontvjapan.com"		=>	"CS16", // 333,	// "ＡＴ-Ｘ"
	"1050.ontvjapan.com"		=>	"CS16", // 342,	// "ヒストリーチャンネル"
	"803ch.epgdata.ontvjapan"	=>	"CS16", // 803,	// "スカチャン８０３"
	"804ch.epgdata.ontvjapan"	=>	"CS16", // 804,	// "スカチャン８０４"
	"1207.ontvjapan.com"		=>	"CS16", // 290,	// "ＳＫＹ・ＳＴＡＧＥ"

	"1007.ontvjapan.com"		=>	"CS18", // 240,	// "ムービープラスＨＤ"
	"1027.ontvjapan.com"		=>	"CS18", // 262,	// "ゴルフネットワーク"
	"1074.ontvjapan.com"		=>	"CS18", // 314,	// "ＬａＬａ　ＨＤ"

	"1073.ontvjapan.com"		=>	"CS20", // 307,	// "フジテレビＯＮＥ"
	"1072.ontvjapan.com"		=>	"CS20", // 308,	// "フジテレビＴＷＯ"
	"1047.ontvjapan.com"		=>	"CS20", // 332,	// "アニマックス"
	"1062.ontvjapan.com"		=>	"CS20", // 340,	// "ディスカバリー"
	"1193.ontvjapan.com"		=>	"CS20", // 341,	// "アニマルプラネット"

	
	"1120.ontvjapan.com"		=>	"CS22", // 161,	// "ＱＶＣ"
	"185ch.epgdata.ontvjapan"	=>	"CS22", // 185,	// "プライム３６５．ＴＶ"
	"1015.ontvjapan.com"		=>	"CS22", // 293,	// "ファミリー劇場"
	"3201.ontvjapan.com"		=>	"CS22", // 301,	// "ＴＢＳチャンネル"
	"1090.ontvjapan.com"		=>	"CS22", // 304,	// "ディズニーチャンネル"
	"1022.ontvjapan.com"		=>	"CS22", // 325,	// "MUSIC ON! TV"

	"1076.ontvjapan.com"		=>	"CS22", // 351,	// "ＴＢＳニュースバード"

	"1068.ontvjapan.com"		=>	"CS24", // 257,	// "日テレＧ＋ＨＤ"
	"300ch.epgdata.ontvjapan"	=>	"CS24", // 300,	// "日テレプラス"
	"1208.ontvjapan.com"		=>	"CS24", // 321,	// "Music Japan TV"
	"2002.ontvjapan.com"		=>	"CS24", // 350,	// "日テレＮＥＷＳ２４"
	"1052.ontvjapan.com"		=>	"CS24", // 362,	// "旅チャンネル"
	/*
	"110ch.epgdata.ontvjapan"	=>	"CS22", 110,	// "ワンテンポータル"
	"101ch.epgdata.ontvjapan"	=>	"CS22", 101,	// "宝塚プロモチャンネル"
	"147ch.epgdata.ontvjapan"	=>	"CS22", 147,	// "ＣＳ日本番組ガイド"
	"160ch.epgdata.ontvjapan"	=>	"CS22", // 160,	// "Ｃ-ＴＢＳウエルカム"
	*/
);


// 地上デジタルチャンネルテーブルsettings/gr_channel.phpが存在するならそれを
// 優先する
if( file_exists( INSTALL_PATH."/settings/gr_channel.php" ) ) {
	unset($GR_CHANNEL_MAP);
	include_once( INSTALL_PATH."/settings/gr_channel.php" );
}

//
// settings/site_conf.phpがあればそれを優先する
//
if( file_exists( INSTALL_PATH."/settings/site_conf.php" ) ) {
	unset($GR_CHANNEL_MAP);
	unset($RECORD_MODE);
	include_once( INSTALL_PATH."/settings/site_conf.php" );
}

// Deprecated
// カスタマイズした設定をロードし、デフォルト設定をオーバライドする
// unsetはカスタム設定ファイルの責任で行う
if( file_exists( INSTALL_PATH."/settings/config_custom.php" ) ) {
	include_once( INSTALL_PATH."/settings/config_custom.php" );
}


// DBテーブル情報　以下は変更しないでください

define( "RESERVE_TBL",  "reserveTbl" );						// 予約テーブル
define( "PROGRAM_TBL",  "programTbl" );						// 番組表
define( "CHANNEL_TBL",  "channelTbl" );						// チャンネルテーブル
define( "CATEGORY_TBL", "categoryTbl" );					// カテゴリテーブル
define( "KEYWORD_TBL", "keywordTbl" );						// キーワードテーブル
// ログテーブル
define( "LOG_TBL", "logTbl" );
?>
