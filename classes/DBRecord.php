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
			$sqlstr = "SELECT * FROM {$this->__table}";
			$sqlstr .= " WHERE {$property} = ?";
			$stmt = $this->db->prepare( $sqlstr );
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

	function createTable( $tblstring )
	{
		$sqlstr = "CREATE TABLE";
		if (self::getDbType() == 'mysql')
		{
			$sqlstr .= " IF NOT EXISTS {$this->__table}";
			$sqlstr .= " ({$tblstring}) DEFAULT CHARACTER SET 'utf8'";
		}
		else
			$sqlstr .= " {$this->__table} ({$tblstring})";
		$stmt = $this->db->prepare( $sqlstr );
		if ( $stmt->execute() === false )
			throw new exception( "createTable:テーブル作成失敗" );
		$stmt->closeCursor();
	}

	function fetch_array( $property , $value, $options = null )
	{
		$retval = array();
		$sqlstr = "SELECT * FROM {$this->__table}";
		$sqlstr .= " WHERE {$property} = ?";
		if ( $options != null ) $sqlstr .= "AND {$options}";
		$stmt = $this->db->prepare( $sqlstr );
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
			$sqlstr = "INSERT INTO ".$this->__table." VALUES ( )";
			$stmt = $this->db->prepare( $sqlstr );
			if ($stmt->execute() !== false)
			{
				$this->__id = $this->db->lastInsertId();
				$stmt->closeCursor();
				
				// $this->__record_data読み出し 
				$sqlstr = "SELECT * FROM {$this->__table}";
				$sqlstr .= " WHERE id = ?";
				$stmt = $this->db->prepare( $sqlstr );
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
		if ( $this->__id == 0 ) throw new exception( "get:無効なid" );
		if ( $property === "id" ) return $this->__id;
		if ( $this->__record_data === false ) throw new exception( "get: 無効なレコード" );
		if ( ! array_key_exists( $property, $this->__record_data ) ) throw new exception( "get: $propertyは存在しません" );
		return stripslashes($this->__record_data[$property]);
	}

	function delete()
	{
		if ( $this->__id == 0 ) throw new exception( "delete:無効なid" );
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
				$this->updateRow($this->__table, $this->__record_data, array('id' => $this->__id));
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
			$sqlstr = "SELECT * FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sqlstr );
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
			$sqlstr = "DELETE FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sqlstr );
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
			$sqlstr = "SELECT COUNT(*) FROM {$tbl->__table} {$options}";
			$stmt = $tbl->db->prepare( $sqlstr );
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
