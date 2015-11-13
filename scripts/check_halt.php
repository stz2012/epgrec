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

	// 省電力の設定がOFFの場合
	if ($settings->use_power_reduce == 0)
		exit(1);

	// 現在録画中である
	if ($db_obj->isRecordingNow())
	{
		UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】現在録画中');
		exit(1);
	}

	// 現在から２時間以内に録画予約がある
	if ($db_obj->isExistReservationWithInMinutes(120))
	{
		UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】２時間以内に録画予約あり');
		exit(1);
	}

	// 起動してから１時間未満の場合
	if (UtilSQLite::isExistEventWithInHours('wakeup', 1))
	{
		UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】起動してから１時間未満');
		exit(1);
	}

	// ジョブ処理中である
	if (count(glob('/var/lock/subsys/auto_*')) > 0)
	{
		UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】ジョブ処理中');
		exit(1);
	}

	// エンコード処理中である
	if (count(glob('/var/lock/subsys/autoenc_*')) > 0)
	{
		UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】エンコード処理中');
		exit(1);
	}
}
catch ( Exception $e )
{
	UtilSQLite::outEventLog('chkstatus', '【自動終了チェック】'.$e->getMessage());
}

exit(0);
?>
