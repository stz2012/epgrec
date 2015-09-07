#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');

$settings = Settings::factory();

try
{
	$recs = DBRecord::createRecords( RESERVE_TBL );

	// DB接続
	ModelBase::setConnectionInfo($settings->getConnInfo());
	$db_obj = new ModelBase();
	if ( $db_obj->isConnect() === false )
		exit( "mysql connection fail" );

	foreach( $recs as $rec )
	{
		$title = $rec->title."(".date("Y/m/d", toTimestamp($rec->starttime)).")";
		$db_obj->updateRow('mt_cds_object', array('dc_title' => $title),
													array('dc_title' => $rec->path));
		
		$desc = "dc:description=".trim($rec->description);
		$desc .= "&epgrec:id=".$rec->id;
		$db_obj->updateRow('mt_cds_object', array('metadata' => $desc),
													array('dc_title' => $rec->path));
	}
}
catch( Exception $e )
{
	exit( $e->getMessage() );
}
?>
