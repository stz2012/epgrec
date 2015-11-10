<?php
/**
 * Epgrec設定クラス
 * @package SimpleXMLElement
 * @subpackage Settings
 */
class Settings extends SimpleXMLElement
{
	/**
	 * 設定取得
	 * @return object 
	 */
	public static function factory()
	{
		$xmlfile = UtilSQLite::getOptionXml();
		if ( $xmlfile != '' ) )
		{	// 既存ファイル読込
			return new self($xmlfile);
		}
		else
		{	// 初回起動
			return self::getDefaults();
		}
	}

	/**
	 * デフォルト設定取得
	 * @return object 
	 */
	public static function getDefaults()
	{
		$xmlfile = '<?xml version="1.0" encoding="UTF-8" ?><epgrec></epgrec>';
		$xml = new self($xmlfile);

		// データベースドライバー名
		$xml->db_type = 'mysql';
		// データベースホスト名
		$xml->db_host = 'localhost';
		// データベースポート
		$xml->db_port = 3306;
		// データベース接続ユーザー名
		$xml->db_user = 'yourname';
		// データベース接続パスワード
		$xml->db_pass = 'yourpass';
		// 使用データベース名
		$xml->db_name = 'yourdbname';
		// テーブル接頭辞
		$xml->tbl_prefix = 'Recorder_';

		// 録画保存ディレクトリ
		$xml->spool = '/video';
		// サムネールの使用
		$xml->use_thumbs = 0;
		// ffmpegのパス
		$xml->ffmpeg = '/usr/bin/ffmpeg';
		// サムネール保存ディレクトリ
		$xml->thumbs = '/htdocs/epgrec/thumbs';
		// EPG取得用テンポラリファイルの設定：録画データ
		$xml->temp_data = '/tmp/__temp.ts';
		// EPG取得用テンポラリファイルの設定：XMLファイル
		$xml->temp_xml = '/tmp/__temp.xml';
		// 使用コマンドのパス設定：epgdump
		$xml->epgdump = '/usr/local/bin/epgdump';
		// 使用コマンドのパス設定：at
		$xml->at = '/usr/bin/at';
		// 使用コマンドのパス設定：atrm
		$xml->atrm = '/usr/bin/atrm';
		// 使用コマンドのパス設定：sleep
		$xml->sleep = '/bin/sleep';
		// Webサーバーのユーザー名
		$xml->www_user = 'www-data';
		// Webサーバーのグループ名
		$xml->www_group = 'www-data';
		// 省電力の設定
		$xml->use_power_reduce = 0;
		// 録画スタート前に起動させる時間（分）
		$xml->wakeup_before = 45;
		// EPGを取得する間隔（時間）
		$xml->getepg_timer = 6;

		// 地デジチューナーの台数
		$xml->gr_tuners = 1;
		// BSチューナーの台数
		$xml->bs_tuners = 0;
		// CS録画の有無
		$xml->cs_rec_flg = 0;
		// 録画開始の余裕時間（秒）
		$xml->former_time = 20;
		// 録画時間を長めにする（秒）
		$xml->extra_time = 0;
		// 連続した番組の予約
		$xml->force_cont_rec = 0;
		// 録画コマンドの切り替え時間（秒）
		$xml->rec_switch_time = 5;
		// 優先する録画モード
		$xml->autorec_mode = 0;
		// 録画ファイル名の形式
		$xml->filename_format = '%TYPE%%CH%_%ST%_%ET%';
		// ページに表示する番組表の長さ（時間）
		$xml->program_length = 8;
		// 1局あたりの幅（px）
		$xml->ch_set_width = 150;
		// 1時間あたりの高さ（px）
		$xml->height_per_hour = 120;

		// インストール済みかどうか
		$xml->is_installed = 0;

		return $xml;
	}

	/**
	 * DB接続情報取得
	 * @return array 
	 */
	public function getConnInfo()
	{
		return array(
			'type'   => $this->db_type,
			'host'   => $this->db_host,
			'port'   => $this->db_port,
			'dbname' => $this->db_name,
			'dbuser' => $this->db_user,
			'dbpass' => $this->db_pass
		);
	}

	/**
	 * プロパティ存在チェック
	 * @param string $property プロパティ名
	 * @return bool 
	 */
	public function exists( $property )
	{
		$xml_def = self::getDefaults();
		return (int)count( $xml_def->{$property} );
	}

	/**
	 * データをセット
	 * @param array $POST_DATA 
	 */
	public function post( $POST_DATA )
	{
		if (!is_array($POST_DATA))
			return;
		foreach ( $POST_DATA as $key => $value )
		{
			if ( $this->exists($key) )
				$this->{$key} = trim($value);
		}
	}

	/**
	 * XMLを保存
	 */
	public function save()
	{
		UtilSQLite::updOptionXml($this->asXML());
	}
}
?>
