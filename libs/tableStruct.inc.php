<?php
// データベーステーブル定義


// 予約テーブル
define( "RESERVE_STRUCT", 
	"id integer not null auto_increment primary key,".				// ID
	"channel_disc varchar(128) not null default 'none',".			// channel disc
	"channel_id integer not null  default '0',".					// channel ID
	"program_id integer not null default '0',".						// Program ID
	"type varchar(8) not null default 'GR',".						// 種別（GR/BS/CS）
	"channel varchar(10) not null default '0',".					// チャンネル
	"title varchar(512) not null default 'none',".					// タイトル
	"description varchar(512) not null default 'none',".			// 説明 text->varchar
	"category_id integer not null default '0',".					// カテゴリID
	"starttime datetime not null default '1970-01-01 00:00:00',".	// 開始時刻
	"endtime datetime not null default '1970-01-01 00:00:00',".		// 終了時刻
	"job integer not null default '0',".							// job番号
	"path blob default null,".										// 録画ファイルパス
	"complete boolean not null default '0',".						// 完了フラグ
	"reserve_disc varchar(128) not null default 'none',".			// 識別用hash
	"autorec integer not null default '0',".						// キーワードID
	"mode integer not null default '0',".							// 録画モード
	"dirty boolean not null default '0',".							// ダーティフラグ
	"index reserve_ch_idx (channel_disc),".							// インデックス
	"index reserve_st_idx (starttime)".
	""
);


// 番組表テーブル
define( "PROGRAM_STRUCT",
	"id integer not null auto_increment primary key,".				// ID
	"channel_disc varchar(128) not null default 'none',".			// channel disc
	"channel_id integer not null default '0',".						// channel ID
	"type varchar(8) not null default 'GR',".						// 種別（GR/BS/CS）
	"channel varchar(10) not null default '0',".					// チャンネル
	"title varchar(512) not null default 'none',".					// タイトル
	"description varchar(512) not null default 'none',".			// 説明 text->varchar
	"category_id integer not null default '0',".					// カテゴリID
	"starttime datetime not null default '1970-01-01 00:00:00',".	// 開始時刻
	"endtime datetime not null default '1970-01-01 00:00:00',".		// 終了時刻
	"program_disc varchar(128) not null default 'none',".	 		// 識別用hash
	"autorec boolean not null default '1',".						// 自動録画有効無効
	"index program_ch_idx (channel_disc),".							// インデックス
	"index program_st_idx (starttime)".
	""
);


define( "CHANNEL_STRUCT",
	"id integer not null auto_increment primary key,".				// ID
	"type varchar(8) not null default 'GR',".						// 種別
	"channel varchar(10) not null default '0',".					// channel
	"name varchar(512) not null default 'none',".					// 表示名
	"channel_disc varchar(128) not null default 'none',".			// 識別用hash
	"sid varchar(64) not null default 'hd',".						// サービスID用02/23/2010追加
	"skip boolean not null default '0'".							// チャンネルスキップ用03/13/2010追加
	""
);

define( "CATEGORY_STRUCT",
	"id integer not null auto_increment primary key,".				// ID
	"name_jp varchar(512) not null default 'none',".				// 表示名
	"name_en varchar(512) not null default 'none',".				// 同上
	"category_disc varchar(128) not null default 'none'"			// 識別用hash
);


define( "KEYWORD_STRUCT",
	"id integer not null auto_increment primary key,".				// ID
	"keyword varchar(512) not null default '*',".					// 表示名
	"type varchar(8) not null default '*',".						// 種別
	"channel_id integer not null default '0',".						// channel ID
	"category_id integer not null default '0',".					// カテゴリID
	"use_regexp boolean not null default '0',".						// 正規表現を使用するなら1
	"autorec_mode integer not null default '0',".					// 自動録画のモード02/23/2010追加
	"weekofday enum ('0','1','2','3','4','5','6','7' ) not null default '7'".// 曜日、同追加
	",prgtime enum ('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24') not null default '24'".	// 時間　03/13/2010追加
	""
);

define( "LOG_STRUCT",
	"id integer not null auto_increment primary key".				// ID
	",logtime  datetime not null default '1970-01-01 00:00:00'".	// 記録日時
	",level integer not null default '0'".							// エラーレベル
	",message varchar(512) not null default ''".
	""
);

?>
