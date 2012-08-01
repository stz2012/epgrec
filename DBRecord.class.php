<?php
include_once( 'config.php' );
include_once( 'Settings.class.php' );

class DBRecord {
	protected $__table;
	
	protected $__record_data;
	protected $__f_dirty;
	
	public $__id;
	
	protected static $__dbh = false;
	protected static $__settings = false;
	
    function __construct( $table, $property = null, $value = null ) {
		
		$this->__f_dirty = false;
		$this->__record_data = false;
		
		if( self::$__dbh === false ) {
			self::$__settings = Settings::factory();
			self::$__dbh = @mysql_connect( self::$__settings->db_host , self::$__settings->db_user, self::$__settings->db_pass );
			if( self::$__dbh === false ) throw new exception( "construct:データベースに接続できない" );
			$sqlstr = "use ".self::$__settings->db_name;
			$res = $this->__query($sqlstr);
			if( $res === false ) throw new exception("construct: " . $sqlstr );
			$sqlstr = "set NAMES utf8";
			$res = $this->__query($sqlstr);
		}
		$this->__table = self::$__settings->tbl_prefix.$table;
		
		if( ($property == null) || ($value == null) ) {
			// レコードを特定する要素が指定されない場合はid=0として空のオブジェクトを作成する
			$this->__id = 0;
		}
		else {
			$sqlstr = "SELECT * FROM ".$this->__table.
			            " WHERE ".mysql_real_escape_string( $property ).
			              "='".mysql_real_escape_string( $value )."'";
			
			$res = $this->__query( $sqlstr );
			$this->__record_data = mysql_fetch_array( $res , MYSQL_ASSOC );
			if( $this->__record_data === false ) throw new exception( "construct:".$this->__table."に".$property."=".$value."はありません" );
			// 最初にヒットした行のidを使用する
			$this->__id = $this->__record_data['id'];
		}
		
		return;
	}
	
	function createTable( $tblstring ) {
		$sqlstr = "use ".self::$__settings->db_name;
		$res = $this->__query($sqlstr);
		if( $res === false ) throw new exception("createTable: " . $sqlstr );
		$sqlstr = "CREATE TABLE IF NOT EXISTS ".$this->__table." (" .$tblstring.") DEFAULT CHARACTER SET 'utf8'";
		$result = $this->__query( $sqlstr );
		if( $result === false ) throw new exception( "createTable:テーブル作成失敗" );
	}
	
	protected function __query( $sqlstr ) {
		if( self::$__dbh === false ) throw new exception( "__query:DBに接続されていない" );
		
		$res = @mysql_query( $sqlstr, self::$__dbh );
		if( $res === false ) throw new exception( "__query:DBクエリ失敗:".$sqlstr );
		return $res;
	}
	
	function fetch_array( $property , $value, $options = null ) {
		$retval = array();
		
		$sqlstr = "SELECT * FROM ".$this->__table.
		            " WHERE ".mysql_real_escape_string( $property ).
		              "='".mysql_real_escape_string( $value )."'";
		
		if( $options != null ) {
			$sqlstr .= "AND ".$options;
		}
		$res = $this->__query( $sqlstr );
		while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			array_push( $retval, $row );
		}
		
		return $retval;
	}
	
	function __set( $property, $value ) {
		if( $property === "id" ) throw new exception( "set:idの変更は不可" );
		// id = 0なら空の新規レコード作成
		if( $this->__id == 0 ) {
			$sqlstr = "INSERT INTO ".$this->__table." VALUES ( )";
			$res = $this->__query( $sqlstr );
			$this->__id = mysql_insert_id();
			
			// $this->__record_data読み出し 
			$sqlstr = "SELECT * FROM ".$this->__table.
			            " WHERE id = '".$this->__id."'";
			
			$res = $this->__query( $sqlstr );
			$this->__record_data = mysql_fetch_array( $res , MYSQL_ASSOC );
		}
		if( $this->__record_data === false ) throw new exception("set: DBの異常？" );
		
		if( array_key_exists( $property, $this->__record_data ) ) {
			$this->__record_data[$property] = mysql_real_escape_string($value);
			$this->__f_dirty = true;
		}
		else {
			throw new exception("set:$property はありません" );
		}
	}
	
	function __get( $property ) {
		if( $this->__id == 0 ) throw new exception( "get:無効なid" );
		if( $property === "id" ) return $this->__id;
		if( $this->__record_data === false ) throw new exception( "get: 無効なレコード" );
		if( ! array_key_exists( $property, $this->__record_data ) ) throw new exception( "get: $propertyは存在しません" );
		
		return stripslashes($this->__record_data[$property]);
	}
	
	function delete() {
		if( $this->__id == 0 ) throw new exception( "delete:無効なid" );
		
		$sqlstr = "DELETE FROM ".$this->__table." WHERE id='".$this->__id."'";
		$this->__query( $sqlstr );
		$this->__id = 0;
		$this->__record_data = false;
		$this->__f_dirty = false;
	}
	
	function update() {
		if( $this->__id != 0 ) { 
			if( $this->__f_dirty ) {
				$sqlstr = "UPDATE ".$this->__table." SET";
				foreach( $this->__record_data as $property => $value ) {
					if( $property === "id" ) continue;
					$sqlstr .= " ".$property." = '".$value."',";
				}
				$sqlstr = rtrim($sqlstr, "," );
				$sqlstr .= " WHERE id = '".$this->__id."'";
				$res = $this->__query($sqlstr);
				if( $res === false ) throw new exception( "close: アップデート失敗" );
			}
			$this->__f_dirty = false;
		}
	}
	
	// countを実行する
	static function countRecords( $table, $options = "" ) {
		try{
			$tbl = new self( $table );
			$sqlstr = "SELECT COUNT(*) FROM " . $tbl->__table ." " . $options;
			$result = $tbl->__query( $sqlstr );
		}
		catch( Exception $e ) {
			throw $e;
		}
		if( $result === false ) throw new exception("COUNT失敗");
		$retval = mysql_fetch_row( $result );
		return $retval[0];
	}
	
	// DBRecordオブジェクトを返すstaticなメソッド
	static function createRecords( $table, $options = "" ) {
		$retval = array();
		$arr = array();
		try{
			$tbl = new self( $table );
			$sqlstr = "SELECT * FROM ".$tbl->__table." " .$options;
			$result = $tbl->__query( $sqlstr );
		}
		catch( Exception $e ) {
			throw $e;
		}
		if( $result === false ) throw new exception("レコードが存在しません");
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			array_push( $retval, new self( $table,  'id', $row['id'] ) );
		}
		return $retval;
	}
	
	// デストラクタ
	function __destruct() {
		// 呼び忘れに対応
		if( $this->__id != 0 ) {
			$this->update();
		}
		$this->__id = 0;
		$this->__record_data = false;
	}
}
?>
