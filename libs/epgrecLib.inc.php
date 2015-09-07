<?php

define( "EPGREC_INFO" , 0 );
define( "EPGREC_WARN" , 1 );
define( "EPGREC_ERROR", 2 );

class RecException extends Exception {
	
	private $level = EPGREC_INFO;
	
	public function __construct( $mesg, $l = EPGREC_INFO ) {
		parent::__construct( $mesg );
		$this->level = $l;
	}
	
	public function getLevel() {
		return $this->level;
	}
}


function reclog( $message , $level = EPGREC_INFO ) {
	
	try {
		$log = new DBRecord( LOG_TBL );
		
		$log->logtime = date("Y-m-d H:i:s");
		$log->level = $level;
		$log->message = $message;
	}
	catch( Exception $e ) {
		// 
	}
}

?>
