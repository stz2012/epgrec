#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');

$settings = Settings::factory();

$procMng = new EpgrecProcMng();
if ( !$procMng->createDaemon() )
	exit;

// ユーザー/グループの切り替えを試みる
if (intval($settings->use_power_reduce) != 0 )
{
	$userinfo = posix_getpwnam( $settings->www_user );
	$groupinfp = posix_getgrnam( $settings->www_group );

	posix_setgid( $groupinfo['gid'] );
	posix_setuid( $userinfo['uid'] );
}


$bs_proc = false;
$gr_procs = array();
$cs1_proc = false;
$cs2_proc = false;

$temp_data_bs  = $settings->temp_data.".bs";
$temp_data_cs1 = $settings->temp_data.".cs1";
$temp_data_cs2 = $settings->temp_data.".cs2";
$temp_data_gr  = $settings->temp_data.".gr";

if ( file_exists( $settings->temp_data ) ) @unlink( $settings->temp_data );

// 地上波を処理する
if ( $settings->gr_tuners != 0 )
{
	foreach( $GR_CHANNEL_MAP as $value )
	{
		// 録画重複チェック
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND type = 'GR' AND endtime > now() AND starttime < addtime( now(), '00:01:10')" );
		if ( ($num < $settings->gr_tuners) && check_epgdump_file($temp_data_gr.$value) )
		{
			$cmdline = "CHANNEL=".$value." DURATION=60 TYPE=GR TUNER=0 MODE=0 OUTPUT=".$temp_data_gr.$value." ".DO_RECORD . " >/dev/null 2>&1";
			$procObj = new EpgrecProc( $cmdline );
			$cmdline = STORE_PRG_CMD." GR {$temp_data_gr}{$value} {$value}";
			$procObj->addSubCmd( $cmdline );
			$procMng->addQueue( $procObj );
		}
	}
}

// BSを処理する
if ( $settings->bs_tuners != 0 )
{
	// 録画重複チェック
	$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
	if ( ($num < $settings->bs_tuners) && check_epgdump_file($temp_data_bs) )
	{
		$cmdline = "CHANNEL=".BS_EPG_CHANNEL." DURATION=180 TYPE=BS TUNER=0 MODE=0 OUTPUT=".$temp_data_bs." ".DO_RECORD . " >/dev/null 2>&1";
		$procObj = new EpgrecProc( $cmdline );
		$cmdline = STORE_PRG_CMD." BS {$temp_data_bs}";
		$procObj->addSubCmd( $cmdline );
		$procMng->addQueue( $procObj );
	}

	// CS
	if ($settings->cs_rec_flg != 0)
	{
		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if ( ($num < $settings->bs_tuners) && check_epgdump_file($temp_data_cs1) )
		{
			$cmdline = "CHANNEL=".CS1_EPG_CHANNEL." DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$temp_data_cs1." ".DO_RECORD . " >/dev/null 2>&1";
			$procObj = new EpgrecProc( $cmdline );
			$cmdline = STORE_PRG_CMD." CS1 {$temp_data_cs1}";
			$procObj->addSubCmd( $cmdline );
			$procMng->addQueue( $procObj );
		}

		$num = DBRecord::countRecords(  RESERVE_TBL, "WHERE complete = '0' AND (type = 'BS' OR type = 'CS') AND endtime > now() AND starttime < addtime( now(), '00:03:05')" );
		if ( ($num < $settings->bs_tuners) && check_epgdump_file($temp_data_cs2) )
		{
			$cmdline = "CHANNEL=".CS2_EPG_CHANNEL." DURATION=120 TYPE=CS TUNER=0 MODE=0 OUTPUT=".$temp_data_cs2." ".DO_RECORD . " >/dev/null 2>&1";
			$procObj = new EpgrecProc( $cmdline );
			$cmdline = STORE_PRG_CMD." CS2 {$temp_data_cs2}";
			$procObj->addSubCmd( $cmdline );
			$procMng->addQueue( $procObj );
		}
	}
}

// 終了を待つ
$procMng->waitQueue();

garbageClean();			//  不要プログラム削除
doKeywordReservation();	// キーワード予約

if ( intval($settings->use_power_reduce) != 0 )
{
	if ( file_exists(INSTALL_PATH. "/settings/wakeupvars.xml") )
	{
		$wakeupvars_text = file_get_contents( INSTALL_PATH. "/settings/wakeupvars.xml" );
		$wakeupvars = new SimpleXMLElement($wakeupvars_text);

		// getepg終了時を書込み
		$wakeupvars->getepg_time = time();
		// 起動理由を調べる
		if ( strcasecmp( "getepg", $wakeupvars->reason ) == 0 )
		{
			// 1時間以内に録画はないか？
			$count = DBRecord::countRecords( RESERVE_TBL, " WHERE complete <> '1' AND starttime < addtime( now(), '01:00:00') AND endtime > now()" );
			if ( $count != 0 ) {	// 録画があるなら録画起動にして終了
				$wakeupvars->reason = "reserve";
			}
			else {
				exec( $settings->shutdown . " -h +".$settings->wakeup_before );
			}
		}
		$wakeupvars->asXML(INSTALL_PATH. "/settings/wakeupvars.xml");
	}
}
exit();
?>
