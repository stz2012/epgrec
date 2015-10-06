<?php
class DBRecord extends ModelBase
{
	public $__id;
	protected $__table;
	protected $__record_data;
	protected $__f_dirty;
	protected static $__settings = false;

	function __construct( $table, $property = null, $value = null )
	{
		$this->__f_dirty = false;
		$this->__record_data = false;
		
		if ( $this->db === false )
		{
			self::$__settings = Settings::factory();
			$this->setConnectionInfo(self::$__settings->getConnInfo());
			$this->initDb();
			if ( $this->db === false )
				throw new exception( "construct:データベースに接続できない" );
		}
		$this->__table = self::$__settings->tbl_prefix.$table;
		
		if ( ($property == null) || ($value == null) )
		{
			// レコードを特定する要素が指定されない場合はid=0として空のオブジェクトを作成する
			$this->__id = 0;
		}
		else
		{
			$sql = "SELECT * FROM {$this->__table}";
			$sql .= " WHERE {$property} = ?";
			$stmt = $this->db->prepare( $sql );
			$param = $this->defineParamType($value);
			$stmt->bindValue(1, $value, $param);
			if ($stmt->execute() !== false)
				$this->__record_data = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			if ( $this->__record_data === false )
				throw new exception( "construct:".$this->__table."に".$property."=".$value."はありません" );
			// 最初にヒットした行のidを使用する
			$this->__id = $this->__record_data['id'];
		}
	}

	function createTable()
	{
		$sql = "CREATE TABLE";
		if (self::getDbType() == 'mysql')
		{
			$sql .= " IF NOT EXISTS {$this->__table}";
			$sql .= " ({$this->_getTableStruct()}) DEFAULT CHARACTER SET 'utf8'";
		}
		else
			$sql .= " {$this->__table} ({$this->_getTableStruct()})";
		$stmt = $this->db->prepare( $sql );
		if ( $stmt->execute() === false )
			throw new exception( "createTable:テーブル作成失敗" );
		$stmt->closeCursor();
		$this->_createIndex();
	}

	function fetch_array( $property , $value, $options = null )
	{
		$retval = array();
		$sql = "SELECT * FROM {$this->__table}";
		$sql .= " WHERE {$property} = ?";
		if ( $options != null ) $sql .= "AND {$options}";
		$stmt = $this->db->prepare( $sql );
		$param = $this->defineParamType($value);
		$stmt->bindValue(1, $value, $param);
		if ($stmt->execute() !== false)
			$retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $retval;
	}

	function __set( $property, $value )
	{
		if ( $property === "id" ) throw new exception( "set:idの変更は不可" );
		// id = 0なら空の新規レコード作成
		if ( $this->__id == 0 )
		{
			if (self::getDbType() == 'pgsql')
				$sql = "INSERT INTO {$this->__table} (id) VALUES (nextval('{$this->__table}_id_seq'))";
			else if (self::getDbType() == 'sqlite')
				$sql = "INSERT INTO {$this->__table} DEFAULT VALUES";
			else
				$sql = "INSERT INTO {$this->__table} VALUES ( )";
			$stmt = $this->db->prepare( $sql );
			if ($stmt->execute() !== false)
			{
				if (self::getDbType() == 'pgsql')
					$this->__id = $this->db->lastInsertId("{$this->__table}_id_seq");
				else
					$this->__id = $this->db->lastInsertId();
				$stmt->closeCursor();
				
				// $this->__record_data読み出し 
				$sql = "SELECT * FROM {$this->__table}";
				$sql .= " WHERE id = ?";
				$stmt = $this->db->prepare( $sql );
				$stmt->bindValue(1, $this->__id, PDO::PARAM_INT);
				if ($stmt->execute() !== false)
					$this->__record_data = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			}
		}
		if ( $this->__record_data === false )
			throw new exception("set: DBの異常？" );
		
		if ( array_key_exists( $property, $this->__record_data ) )
		{
			$this->__record_data[$property] = $value;
			$this->__f_dirty = true;
		}
		else
			throw new exception("set:$property はありません" );
	}

	function __get( $property )
	{
		if ( $this->__id == 0 )
			throw new exception( "get:無効なid" );
		if ( $property === "id" )
			return $this->__id;
		if ( $this->__record_data === false )
			throw new exception( "get: 無効なレコード" );
		if ( ! array_key_exists( $property, $this->__record_data ) )
			throw new exception( "get: $propertyは存在しません" );
		return stripslashes($this->__record_data[$property]);
	}

	function delete()
	{
		if ( $this->__id == 0 )
			throw new exception( "delete:無効なid" );
		$this->deleteRow($this->__table, array('id' => $this->__id));
		$this->__id = 0;
		$this->__record_data = false;
		$this->__f_dirty = false;
	}

	function update()
	{
		if ( $this->__id != 0 )
		{ 
			if ( $this->__f_dirty )
			{
				//UtilLog::writeLog("レコード更新: ".print_r($this->__record_data, true), 'DEBUG');
				$this->updateRow($this->__table, $this->__record_data, array('id' => $this->__id));
			}
			$this->__f_dirty = false;
		}
	}

	// DBRecordオブジェクトを返すstaticなメソッド
	static function createRecords( $table, $options = "" )
	{
		$retval = array();
		try
		{
			$tbl = new self( $table );
			$sql = "SELECT * FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sql );
			if ( $stmt->execute() === false )
				throw new exception("レコードが存在しません");
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				array_push( $retval, new self( $table, 'id', $row['id'] ) );
			}
			$stmt->closeCursor();
		}
		catch ( Exception $e )
		{
			throw $e;
		}
		return $retval;
	}

	// deleteを実行する
	static function deleteRecords( $table, $options = "" )
	{
		try
		{
			$tbl = new self( $table );
			$sql = "DELETE FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sql );
			if ( $stmt->execute() === false )
				throw new exception("DELETE失敗");
		}
		catch ( Exception $e )
		{
			throw $e;
		}
	}

	// countを実行する
	static function countRecords( $table, $options = "" )
	{
		$retval = 0;
		try
		{
			$tbl = new self( $table );
			$sql = "SELECT COUNT(*) FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sql );
			if ( $stmt->execute() === false )
				throw new exception("COUNT失敗");
			$arr = $stmt->fetch(PDO::FETCH_NUM);
			$retval = $arr[0];
			$stmt->closeCursor();
		}
		catch ( Exception $e )
		{
			throw $e;
		}
		return $retval;
	}

	private function _getTableStruct()
	{
		if (self::getDbType() == 'pgsql')
		{
			$sql = 'id serial not null primary key';
			$F_TYPE = 'timestamp';
		}
		else if (self::getDbType() == 'sqlite')
		{
			$sql = 'id integer not null primary key autoincrement';
			$F_TYPE = 'timestamp';
		}
		else
		{
			$sql = 'id integer not null primary key auto_increment';
			$F_TYPE = 'datetime';
		}
		switch ($this->__table)
		{
			// 予約テーブル
			case self::$__settings->tbl_prefix.RESERVE_TBL:
				$sql .= ", channel_disc varchar(128) not null default 'none'";			// channel disc
				$sql .= ", channel_id integer not null  default '0'";					// channel ID
				$sql .= ", program_id integer not null default '0'";					// Program ID
				$sql .= ", type varchar(8) not null default 'GR'";						// 種別（GR/BS/CS）
				$sql .= ", channel varchar(10) not null default '0'";					// チャンネル
				$sql .= ", title varchar(512) not null default 'none'";					// タイトル
				$sql .= ", description varchar(512) not null default 'none'";			// 説明 text->varchar
				$sql .= ", category_id integer not null default '0'";					// カテゴリID
				$sql .= ", starttime {$F_TYPE} not null default '2001-01-01 00:00:00'";	// 開始時刻
				$sql .= ", endtime {$F_TYPE} not null default '2001-01-01 00:00:00'";	// 終了時刻
				$sql .= ", job integer not null default '0'";							// job番号
				$sql .= ", path text default null";										// 録画ファイルパス
				$sql .= ", complete boolean not null default '0'";						// 完了フラグ
				$sql .= ", reserve_disc varchar(128) not null default 'none'";			// 識別用hash
				$sql .= ", autorec integer not null default '0'";						// キーワードID
				$sql .= ", mode integer not null default '0'";							// 録画モード
				$sql .= ", dirty boolean not null default '0'";							// ダーティフラグ
				break;

			// 番組表テーブル
			case self::$__settings->tbl_prefix.PROGRAM_TBL:
				$sql .= ", channel_disc varchar(128) not null default 'none'";			// channel disc
				$sql .= ", channel_id integer not null default '0'";					// channel ID
				$sql .= ", type varchar(8) not null default 'GR'";						// 種別（GR/BS/CS）
				$sql .= ", channel varchar(10) not null default '0'";					// チャンネル
				$sql .= ", title varchar(512) not null default 'none'";					// タイトル
				$sql .= ", description varchar(512) not null default 'none'";			// 説明 text->varchar
				$sql .= ", category_id integer not null default '0'";					// カテゴリID
				$sql .= ", starttime {$F_TYPE} not null default '2001-01-01 00:00:00'";	// 開始時刻
				$sql .= ", endtime {$F_TYPE} not null default '2001-01-01 00:00:00'";	// 終了時刻
				$sql .= ", program_disc varchar(128) not null default 'none'";	 		// 識別用hash
				$sql .= ", autorec boolean not null default '1'";						// 自動録画有効無効
				break;

			// チャンネルテーブル
			case self::$__settings->tbl_prefix.CHANNEL_TBL:
				$sql .= ", type varchar(8) not null default 'GR'";						// 種別
				$sql .= ", channel varchar(10) not null default '0'";					// channel
				$sql .= ", name varchar(512) not null default 'none'";					// 表示名
				$sql .= ", channel_disc varchar(128) not null default 'none'";			// 識別用hash
				$sql .= ", sid varchar(64) not null default 'hd'";						// サービスID用02/23/2010追加
				$sql .= ", skip boolean not null default '0'";							// チャンネルスキップ用03/13/2010追加
				break;

			// カテゴリテーブル
			case self::$__settings->tbl_prefix.CATEGORY_TBL:
				$sql .= ", name_jp varchar(512) not null default 'none'";				// 表示名
				$sql .= ", name_en varchar(512) not null default 'none'";				// 同上
				$sql .= ", category_disc varchar(128) not null default 'none'";			// 識別用hash
				break;

			// キーワードテーブル
			case self::$__settings->tbl_prefix.KEYWORD_TBL:
				$sql .= ", keyword varchar(512) not null default '*'";					// 表示名
				$sql .= ", type varchar(8) not null default '*'";						// 種別
				$sql .= ", channel_id integer not null default '0'";					// channel ID
				$sql .= ", category_id integer not null default '0'";					// カテゴリID
				$sql .= ", use_regexp boolean not null default '0'";					// 正規表現を使用するなら1
				$sql .= ", autorec_mode integer not null default '0'";					// 自動録画のモード02/23/2010追加
				$sql .= ", weekofday varchar(1) not null default '0'";					// 曜日、同追加
				$sql .= ", prgtime varchar(2) not null default '24'";					// 時間　03/13/2010追加
				break;

			// ログテーブル
			case self::$__settings->tbl_prefix.LOG_TBL:
				$sql .= ", logtime {$F_TYPE} not null default '2001-01-01 00:00:00'";	// 記録日時
				$sql .= ", level integer not null default '0'";							// エラーレベル
				$sql .= ", message varchar(512) not null default ''";					// メッセージ
				break;
		}
		return $sql;
	}

	private function _createIndex()
	{
		switch ($this->__table)
		{
			// 予約テーブル
			case self::$__settings->tbl_prefix.RESERVE_TBL:
				$sql = "CREATE INDEX reserve_pg_idx ON {$this->__table}(program_id)";
				$stmt = $this->db->prepare( $sql );
				if ( $stmt->execute() === false )
					throw new exception( "createIndex:インデックス作成失敗" );
				$stmt->closeCursor();
				$sql = "CREATE INDEX reserve_st_idx ON {$this->__table}(starttime)";
				$stmt = $this->db->prepare( $sql );
				if ( $stmt->execute() === false )
					throw new exception( "createIndex:インデックス作成失敗" );
				$stmt->closeCursor();
				break;

			// 番組表テーブル
			case self::$__settings->tbl_prefix.PROGRAM_TBL:
				$sql = "CREATE INDEX program_pg_idx ON {$this->__table}(program_disc)";
				$stmt = $this->db->prepare( $sql );
				if ( $stmt->execute() === false )
					throw new exception( "createIndex:インデックス作成失敗" );
				$stmt->closeCursor();
				$sql = "CREATE INDEX program_st_idx ON {$this->__table}(starttime)";
				$stmt = $this->db->prepare( $sql );
				if ( $stmt->execute() === false )
					throw new exception( "createIndex:インデックス作成失敗" );
				$stmt->closeCursor();
				break;
		}
	}

	// デストラクタ
	function __destruct()
	{
		// 呼び忘れに対応
		if ( $this->__id != 0 )
		{
			$this->update();
		}
		$this->__id = 0;
		$this->__record_data = false;
	}
}
?>
