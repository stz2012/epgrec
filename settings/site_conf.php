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
?>
