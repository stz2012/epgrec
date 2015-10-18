#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');
// コマンドライン起動か判別する
if ( ! (isset($argv[0]) && __FILE__ === realpath($argv[0])) )
	exit;

$settings = Settings::factory();
$reserve_id = $argv[1];

try
{
	$rrec = new DBRecord( RESERVE_TBL, 'id' , $reserve_id );
	$rrec->complete = '1';
	
	if ( file_exists( INSTALL_PATH .$settings->spool . '/'. $rrec->path ) )
	{
		// 予約完了
		UtilLog::outLog( "recomplete:: 予約ID：{$rrec->id} {$rrec->channel} {$rrec->title} の録画が完了" );
		
		if ( $settings->mediatomb_update == 1 )
		{
			// ちょっと待った方が確実っぽい
			@exec('sync');
			sleep(15);
			// タイトル更新
			$title = $rrec->title.'('.date('Y/m/d').')';
			$db_obj->updateRow('mt_cds_object', array('dc_title' => $title),
														array('dc_title' => $rrec->path));
			// 説明更新
			$desc = 'dc:description='.trim($rrec->description);
			$desc .= '&epgrec:id='.$reserve_id;
			$db_obj->updateRow('mt_cds_object', array('metadata' => $desc),
														array('dc_title' => $rrec->path));
		}
	}
	else
	{
		// 予約失敗
		UtilLog::outLog( "recomplete:: 予約ID：{$rrec->id} {$rrec->channel} {$rrec->title} の録画に失敗した模様", UtilLog::LV_ERROR );
		$rrec->delete();
	}
}
catch ( Exception $e )
{
	UtilLog::outLog( 'recomplete:: '.$e->getMessage() , UtilLog::LV_ERROR );
	exit( $e->getMessage() );
}
?>
