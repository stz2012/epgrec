<?php
class Keyword extends DBRecord
{
	public function __construct($property = null, $value = null )
	{
		try
		{
			parent::__construct(KEYWORD_TBL, $property, $value );
		}
		catch( Exception $e )
		{
			throw $e;
		}
	}

	static public function search(
		$keyword = "", 
		$use_regexp = false,
		$type = "*", 
		$category_id = 0,
		$channel_id = 0,
		$weekofday = 7,
		$prgtime = 24,
		$limit = 300
	) {
		// ちょっと先を検索する
		$options = " WHERE starttime > '".date("Y-m-d H:i:s", time() + self::$__settings->padding_time + 60 )."'";
		
		if ( $keyword != "" )
		{
			if ( $use_regexp )
				$options .= " AND CONCAT(title,description) REGEXP '".$this->db->quote($keyword)."'";
			else
				$options .= " AND CONCAT(title,description) LIKE '%".$this->db->quote($keyword)."%'";
		}
		
		if ( $type != "*" )
			$options .= " AND type = '".$type."'";
		
		if ( $category_id != 0 )
			$options .= " AND category_id = '".$category_id."'";
		
		if ( $channel_id != 0 )
			$options .= " AND channel_id = '".$channel_id."'";
		
		if ( $weekofday != 7 )
			$options .= " AND WEEKDAY(starttime) = '".$weekofday."'";
		
		if ( $prgtime != 24 )
			$options .= " AND time(starttime) BETWEEN CAST('".sprintf( "%02d:00:00", $prgtime)."' as time) AND CAST('".sprintf("%02d:59:59", $prgtime)."' as time)";
		
		$options .= " ORDER BY starttime ASC  LIMIT ".$limit ;

		$recs = array();
		try
		{
			$recs = DBRecord::createRecords( PROGRAM_TBL, $options );
		}
		catch( Exception $e )
		{
			throw $e;
		}
		return $recs;
	}

	private function getPrograms()
	{
		if ( $this->__id == 0 ) return false;

		$recs = array();
		try
		{
			 $recs = self::search( trim($this->keyword), $this->use_regexp, $this->type, $this->category_id, $this->channel_id, $this->weekofday, $this->prgtime );
		}
		catch( Exception $e ) {
			throw $e;
		}
		return $recs;
	}

	public function reservation()
	{
		if ( $this->__id == 0 ) return;

		$precs = array();
		try
		{
			$precs = $this->getPrograms();
		}
		catch( Exception $e )
		{
			throw $e;
		}

		// 一気に録画予約
		foreach( $precs as $rec )
		{
			try
			{
				if ( $rec->autorec )
				{
					Reservation::simple( $rec->id, $this->__id, $this->autorec_mode );
					reclog( "Keyword.class::キーワードID".$this->id."の録画が予約された");
					usleep( 100 );		// あんまり時間を空けないのもどう?
				}
			}
			catch( Exception $e )
			{
				// 無視
			}
		}
	}
	
	public function delete()
	{
		if ( $this->id == 0 ) return;

		$precs = array();
		try
		{
			$precs = $this->getPrograms();
		}
		catch( Exception $e )
		{
			throw $e;
		}

		// 一気にキャンセル
		foreach( $precs as $rec )
		{
			try
			{
				$reserve = new DBRecord( RESERVE_TBL, "program_id", $rec->id );
				// 自動予約されたもののみ削除
				if ( $reserve->autorec )
				{
					Reservation::cancel( $reserve->id );
					usleep( 100 );		// あんまり時間を空けないのもどう?
				}
			}
			catch( Exception $e )
			{
				// 無視
			}
		}

		try
		{
			parent::delete();
		}
		catch( Exception $e )
		{
			throw $e;
		}
	}

	public function __destruct()
	{
		parent::__destruct();
	}
}
?>
