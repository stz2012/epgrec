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

// 直近の正午時刻を取得
$date_str = date('Y-m-d 12:00:00', strtotime('next day'));
if (date('G') < 12)
	$date_str = date('Y-m-d 12:00:00');

try
{
	$db_obj = new CommonModel();
	$db_obj->setSetting($settings);

	// 現在以降の予約で直近開始時刻の{$settings->wakeup_before}分前の時刻を取得
	$waketime = $db_obj->getImmediateReserveTimeBeforeMinutes($settings->wakeup_before);
	if ($waketime > 0)
	{
		UtilSQLite::outEventLog('chkstatus', '【ハイバネートチェック】次回予約データが存在した');
		sleep(3);
		if (toTimestamp($waketime) < toTimestamp($date_str))
		{
			UtilSQLite::outEventLog('chkstatus', "【ハイバネートチェック】次回予約の {$settings->wakeup_before} 分前に起動時間をセット");
			set_wakealarm($waketime);
		}
		else
		{
			UtilSQLite::outEventLog('chkstatus', '【ハイバネートチェック】直近の正午に起動時間をセット');
			set_wakealarm($date_str);
		}
	}
	else
	{
		UtilSQLite::outEventLog('chkstatus', '【ハイバネートチェック】次回予約データが存在無しのため、直近の正午に起動時間をセット');
		set_wakealarm($date_str);
	}
	sleep(3);
}
catch ( Exception $e )
{
	UtilSQLite::outEventLog('chkstatus', '【ハイバネートチェック】'.$e->getMessage());
}

exit(0);
?>
