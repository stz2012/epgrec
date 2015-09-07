<?php
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
?>
