<?php
include_once('config.php');
include_once( INSTALL_PATH . "/DBRecord.class.php" );
include_once( INSTALL_PATH . "/reclib.php" );
include_once( INSTALL_PATH . "/Reservation.class.php" );
include_once( INSTALL_PATH . '/Settings.class.php' );
include_once( INSTALL_PATH . '/recLog.inc.php' );

class Keyword extends DBRecord {
	
	public function __construct($property = null, $value = null ) {
		try {
			parent::__construct(KEYWORD_TBL, $property, $value );
		}
		catch( Exception $e ) {
			throw $e;
		}
	}
	
	static public function search(  $keyword = "", 
									$use_regexp = false,
									$type = "*", 
									$category_id = 0,
									$channel_id = 0,
									$weekofday = 7,
									$prgtime = 24,
									$limit = 300 ) {
		$sts = Settings::factory();
		
		$dbh = @mysql_connect($sts->db_host, $sts->db_user, $sts->db_pass );
		
		// ちょっと先を検索する
		$options = " WHERE starttime > '".date("Y-m-d H:i:s", time() + $sts->padding_time + 60 )."'";
		
		if( $keyword != "" ) {
			if( $use_regexp ) {
				$options .= " AND CONCAT(title,description) REGEXP '".mysql_real_escape_string($keyword)."'";
			}
			else {
				$options .= " AND CONCAT(title,description) like _utf8'%".mysql_real_escape_string($keyword)."%' collate utf8_unicode_ci";
			}
		}
		
		if( $type != "*" ) {
			$options .= " AND type = '".$type."'";
		}
		
		if( $category_id != 0 ) {
			$options .= " AND category_id = '".$category_id."'";
		}
		
		if( $channel_id != 0 ) {
			$options .= " AND channel_id = '".$channel_id."'";
		}
		
		if( $weekofday != 7 ) {
			$options .= " AND WEEKDAY(starttime) = '".$weekofday."'";
		}
		
		if( $prgtime != 24 ) {
			$options .= " AND time(starttime) BETWEEN cast('".sprintf( "%02d:00:00", $prgtime)."' as time) AND cast('".sprintf("%02d:59:59", $prgtime)."' as time)";
		}
		
		$options .= " ORDER BY starttime ASC  LIMIT ".$limit ;
		
		$recs = array();
		try {
			$recs = DBRecord::createRecords( PROGRAM_TBL, $options );
		}
		catch( Exception $e ) {
			throw $e;
		}
		return $recs;
	}
	
	private function getPrograms() {
		if( $this->__id == 0 ) return false;
		$recs = array();
		try {
			 $recs = self::search( trim($this->keyword), $this->use_regexp, $this->type, $this->category_id, $this->channel_id, $this->weekofday, $this->prgtime );
		}
		catch( Exception $e ) {
			throw $e;
		}
		return $recs;
	}
	
	public function reservation() {
		if( $this->__id == 0 ) return;
		
		$precs = array();
		try {
			$precs = $this->getPrograms();
		}
		catch( Exception $e ) {
			throw $e;
		}
		// 一気に録画予約
		foreach( $precs as $rec ) {
			try {
				if( $rec->autorec ) {
					Reservation::simple( $rec->id, $this->__id, $this->autorec_mode );
					reclog( "Keyword.class::キーワードID".$this->id."の録画が予約された");
					usleep( 100 );		// あんまり時間を空けないのもどう?
				}
			}
			catch( Exception $e ) {
				// 無視
			}
		}
	}
	
	public function delete() {
		if( $this->id == 0 ) return;
		
		$precs = array();
		try {
			$precs = $this->getPrograms();
		}
		catch( Exception $e ) {
			throw $e;
		}
		// 一気にキャンセル
		foreach( $precs as $rec ) {
			try {
				$reserve = new DBRecord( RESERVE_TBL, "program_id", $rec->id );
				// 自動予約されたもののみ削除
				if( $reserve->autorec ) {
					Reservation::cancel( $reserve->id );
					usleep( 100 );		// あんまり時間を空けないのもどう?
				}
			}
			catch( Exception $e ) {
				// 無視
			}
		}
		try {
			parent::delete();
		}
		catch( Exception $e ) {
			throw $e;
		}
	}

	// staticなファンクションはオーバーライドできない
	static function createKeywords( $options = "" ) {
		$retval = array();
		$arr = array();
		try{
			$tbl = new self();
			$sqlstr = "SELECT * FROM ".$tbl->__table." " .$options;
			$result = $tbl->__query( $sqlstr );
		}
		catch( Exception $e ) {
			throw $e;
		}
		if( $result === false ) throw new exception("レコードが存在しません");
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			array_push( $retval, new self('id', $row['id']) );
		}
		return $retval;
	}
	
	public function __destruct() {
		parent::__destruct();
	}
}
?>
