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
		UtilSQLite::outEventLog('chkstatus', '【EPGデータ取得チェック】現在録画中');
		exit(1);
	}

	// 現在から１時間以内に録画予約がある
	$starttime = $db_obj->getReserveTimeWithInMinutes(60);
	if ($starttime > 0)
	{
		$diff_val = toTimestamp($starttime) - time();
		if ($diff_val >= (60 * 30))
		{
			UtilSQLite::outEventLog('chkstatus', '【EPGデータ取得チェック】予約録画前のEPGデータ取得を実行');
			exit(0);
		}
		else
		{
			UtilSQLite::outEventLog('chkstatus', '【EPGデータ取得チェック】３０分以内に録画予約あり');
			exit(1);
		}
	}

	$db_obj = new UtilSQLite();

	// 最終取得してから{$settings->getepg_timer}時間未満の場合
	$sql = "SELECT COUNT(event_id)";
	$sql .= " FROM recorder";
	$sql .= " WHERE DATETIME(event_date) > DATETIME('now', '-{$settings->getepg_timer} hours', 'localtime')";
	$stmt = $db_obj->db->query($sql);
	$cnt = $stmt->fetchColumn();
	$stmt->closeCursor();
	if ($cnt > 0)
	{
		UtilSQLite::outEventLog('chkstatus', "【EPGデータ取得チェック】最終取得してから {$settings->getepg_timer} 時間未満");
		exit(1);
	}
}
catch ( Exception $e )
{
	UtilSQLite::outEventLog('chkstatus', '【EPGデータ取得チェック】'.$e->getMessage());
}

exit(0);
?>
