<?php
include_once("XML/RPC2/Server.php");

include_once('config.php');
include_once( INSTALL_PATH . "/Keyword.class.php" );
include_once( INSTALL_PATH . "/reclib.php" );
include_once( INSTALL_PATH . "/Reservation.class.php" );
include_once( INSTALL_PATH . '/Settings.class.php' );

class EpgrecRpc {
	
	/**
	 * Get channel types.
	 *
	 * @param none
	 * @return array
	 */
	public static function getChannelType() {
		
		$settings = Settings::factory();
		
		$retval = array();
		if($settings->gr_tuners != 0 ) {
			array_push( $retval, XML_RPC2_Value::createFromNative("GR") );
		}
		if($settings->bs_tuners != 0 ) {
			array_push( $retval, XML_RPC2_Value::createFromNative("BS") );
		}
		if($settings->cs_rec_flg != 0 ) {
			array_push( $retval, XML_RPC2_Value::createFromNative("CS") );
		}
		
		return XML_RPC2_Value::createFromNative( $retval, "array" );
	}
	
	/**
	 * Get channel lists.
	 *
	 * @param none
	 * @return array
	 */
	public static function getChannelList() {
		try {
			
			$arr = DBRecord::createRecords( CHANNEL_TBL, " WHERE skip <> '1'" );
			
			$retval = array();
			foreach( $arr as $ch ) {
				$r = array(
					"channel_id" => XML_RPC2_Value::createFromNative((int)($ch->id),"int"),
					"type" => XML_RPC2_Value::createFromNative($ch->type),
					"channel" => XML_RPC2_Value::createFromNative((int)($ch->channel),"int"),
					"name" => XML_RPC2_Value::createFromNative($ch->name, "string"),
				);
				$val = XML_RPC2_Value::createFromNative( $r, "struct" );
				array_push( $retval, $val );
			}
			
			return XML_RPC2_Value::createFromNative( $retval, "array" );
		}
		catch( Exception $e ) {
			if( is_a( $e, "XML_RPC2_Exception" ) ) throw $e;
			else throw new XML_RPC2_FaultException( $e->getMessage(),10 );
		}
	}
	
	/**
	 * Get category lists.
	 *
	 * @param none
	 * @return array
	 */
	public static function getCategoryList() {
		try {
			
			$arr = DBRecord::createRecords( CATEGORY_TBL );
			
			$retval = array();
			foreach( $arr as $cat ) {
				$r = array(
					"cateogry_id" => XML_RPC2_Value::createFromNative((int)($cat->id),"int"),
					"name_jp" => XML_RPC2_Value::createFromNative($cat->name_jp),
					"name_en" => XML_RPC2_Value::createFromNative($cat->name_en),
				);
				$val = XML_RPC2_Value::createFromNative( $r, "struct" );
				array_push( $retval, $val );
			}
			
			return XML_RPC2_Value::createFromNative( $retval, "array" );
		}
		catch( Exception $e ) {
			if( is_a( $e, "XML_RPC2_Exception" ) ) throw $e;
			else throw new XML_RPC2_FaultException( $e->getMessage(),10 );
		}
	}
	
	/**
	 * Search program.
	 *
	 * @param string	keyword		Search words.
	 * @param int		use_regexp
	 * @param string	type
	 * @param int		category_id
	 * @param int		channel_id
	 * @param int		weekofday
	 * @param int		prgtime
	 * @return array
	 */
	public static function searchProgram(
									$keyword, 
									$use_regexp = false,
									$type = "*", 
									$category_id = 0,
									$channel_id = 0,
									$weekofday = 7,
									$prgtime = 24 ) {
		if( $weekofday > 7 ) throw new XML_RPC2_FaultException("weekofday value is invalid");
		if( $prgtime > 24 ) throw new XML_RPC2_FaultException("prgtime value is invalid" );
		
		try {
			$prgs = Keyword::search( $keyword, $use_regexp, $type, $category_id, $channel_id, $weekofday, $prgtime );
			$retval = array();
			foreach( $prgs as $prg ) {
				$ch = new DBRecord( CHANNEL_TBL, "id", $prg->channel_id );
				$num = DBRecord::countRecords( RESERVE_TBL, "WHERE program_id = '".$prg->id."'" );
				$reserve_id = 0;
				if( $num != 0 ) {
					$rec = new DBRecord( RESERVE_TBL, "program_id", $prg->id );
					$reserve_id = $rec->id;
				}
				$r = array (
					"program_id" => XML_RPC2_Value::createFromNative( (int)($prg->id), "int" ),
					"type" => XML_RPC2_Value::createFromNative( $prg->type ),
					"channel_name" => XML_RPC2_Value::createFromNative( $ch->name ),
					"title" => XML_RPC2_Value::createFromNative( $prg->title ),
					"description" => XML_RPC2_Value::createFromNative( $prg->description ),
					"starttime" => XML_RPC2_Value::createFromNative( (int)(toTimestamp($prg->starttime)), "datetime" ),
					"endtime" => XML_RPC2_Value::createFromNative( (int)(toTimestamp($prg->endtime)), "datetime" ),
					"reserve" => XML_RPC2_Value::createFromNative( (int)$reserve_id, "int" ),
				);
				array_push( $retval, XML_RPC2_Value::createFromNative( $r, "struct" ) );
			}
			return XML_RPC2_Value::createFromNative( $retval, "array" );
		}
		catch( Exception $e ) {
			if( is_a( $e, "XML_RPC2_Exception" ) ) throw $e;
			else throw new XML_RPC2_FaultException( $e->getMessage(),10 );
		}
	}
	
	/**
	 * Reserve progarm recording .
	 *
	 * @param int	program_id
	 * @return none
	 */
	 public static function reserveProgram( $program_id ) {
		
		try {
			$settings = Settings::factory();
			
			$num = DBRecord::countRecords( PROGRAM_TBL, "id", $program_id );
			if( $num < 1 ) throw new XML_RPC2_FaultException( "Can't find program" , 10 );
			Reservation::simple( $program_id , 0, $settings->autorec_mode );
		}
		catch( Exception $e ) {
			if( is_a( $e, "XML_RPC2_Exception" ) ) throw $e;
			else throw new XML_RPC2_FaultException( $e->getMessage(),10 );
		}
	}
	
}

$options = array(
	"backend"  => "php",
	"encoding" => "UTF-8",
	"prefix" => "epgrec.",
);

$server = XML_RPC2_Server::create("EpgrecRpc", $options);
$server->handleCall();

?>
