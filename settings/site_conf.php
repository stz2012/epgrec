<?php
$RECORD_MODE = array(
	// ※ 0は必須で、変更不可です。
	0 => array(
		'name' => 'EPG',	// モードの表示名
		'suffix' => '.ts',	// ファイル名のサフィックス
	),
	
	1 => array(
		'name' => 'HDTV',	// 最小のTS
		'suffix' => '.ts',	// do-record.shのカスタマイズが必要
	),
);
// 識別子 => チャンネル番号
$GR_CHANNEL_MAP = array(
	"GR15"	=> "15",  // NHK
	"GR13"	=> "13",  // 教育
	"GR26"	=> "26",  // テレビ新潟
	"GR23"	=> "23",  // 新潟テレビ21
	"GR17"	=> "17",  // 新潟放送
	"GR19"	=> "19",  // 新潟総合テレビ
);
?>
