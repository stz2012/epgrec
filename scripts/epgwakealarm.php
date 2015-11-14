#!/usr/bin/php -q
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');
// コマンドライン起動か判別する
if ( ! (isset($argv[0]) && __FILE__ === realpath($argv[0])) )
	exit;

$settings = Settings::factory();
$action = $argv[1];

try
{
	if ( strcasecmp( $action, 'start' ) == 0 )
	{
		UtilSQLite::outEventLog('wakeup', '通常起動');
		exec( AUTO_GETEPG.' >/dev/null 2>&1' );
	}
	else if( strcasecmp( $action, 'stop' ) == 0 )
	{
		UtilSQLite::outEventLog('shutdown', '通常終了');
	}
}
catch ( Exception $e )
{
	UtilLog::outLog( 'epgwakealarm:: '.$e->getMessage(), UtilLog::LV_ERROR );
	exit( $e->getMessage() );
}
?>
