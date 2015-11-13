#!/usr/bin/php -q
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');
// コマンドライン起動か判別する
if ( ! (isset($argv[0]) && __FILE__ === realpath($argv[0])) )
	exit;

$settings = Settings::factory();
ModelBase::setConnectionInfo($settings->getConnInfo());

try
{
	$db_obj = new CommonModel();
	$db_obj->setSetting($settings);

	// 現在録画中である
	if ($db_obj->isRecordingNow())
	{
		UtilSQLite::outEventLog('chkstatus', '【予約チェック】現在録画中');
		exit(1);
	}

	// 現在から３０分以内に録画予約がある
	if ($db_obj->isExistReservationWithInMinutes(30))
	{
		UtilSQLite::outEventLog('chkstatus', '【予約チェック】３０分以内に録画予約あり');
		exit(1);
	}
}
catch ( Exception $e )
{
	UtilSQLite::outEventLog('chkstatus', '【予約チェック】'.$e->getMessage());
}

exit(0);
?>
