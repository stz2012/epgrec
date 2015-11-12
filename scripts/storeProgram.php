#!/usr/bin/php -q
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( dirname( $script_path ) . '/config.php');
// コマンドライン起動か判別する
if ( ! (isset($argv[0]) && __FILE__ === realpath($argv[0])) )
	exit;

$settings = Settings::factory();
$type = $argv[1];	// GR BS CS1 CS2
$file = $argv[2];	// TSファイル

try
{
	// プライオリティ低に
	pcntl_setpriority(20);

	$xmlfile = '';
	$cmdline = $settings->epgdump.' ';

	if ( $type === 'GR' )
	{
		$ch = $argv[3];	// channel
		$xmlfile = $settings->temp_xml.'.gr'.$ch;
		$cmdline .= $ch.' '.$file.' '.$xmlfile;
	}
	else if ( $type === 'BS' )
	{
		$xmlfile = $settings->temp_xml.'.bs';
		$cmdline .= '/BS '.$file.' '.$xmlfile;
	}
	else if ( $type === 'CS1' || $type === 'CS2' )
	{
		$xmlfile = $settings->temp_xml.'.'.strtolower($type);
		$cmdline .= '/CS '.$file.' '.$xmlfile;
	}
	else
		exit;

	$proc = new EpgrecProc( $cmdline );
	$proc->waitCommand();

	if ( file_exists( $xmlfile ) )
	{
		parse_epgdump_file( $type, $xmlfile );
		@unlink( $xmlfile );
	}
	else
	{
		UtilLog::outLog( "storeProgram:: 正常なXML {$xmlfile} が作成されなかった模様(放送間帯でないなら問題ありません)", UtilLog::LV_WARN );
	}

	if ( file_exists( $file ) )
		@unlink( $file );
}
catch ( Exception $e )
{
	UtilLog::outLog( 'storeProgram:: '.$e->getMessage(), UtilLog::LV_ERROR );
	exit( $e->getMessage() );
}
?>