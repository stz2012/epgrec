<?php
include_once('config.php');
include_once( INSTALL_PATH . '/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );


$arr = DBRecord::createRecords( LOG_TBL, " ORDER BY logtime DESC" );

$smarty = new Smarty();

$smarty->assign( "sitetitle" , "epgrec動作ログ" );
$smarty->assign( "logs", $arr );

$smarty->display( "logTable.html" );
?>