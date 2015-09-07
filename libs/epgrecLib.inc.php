<?php
// ライブラリ

define( "EPGREC_INFO" , 0 );
define( "EPGREC_WARN" , 1 );
define( "EPGREC_ERROR", 2 );

function reclog( $message , $level = EPGREC_INFO )
{
	
	try {
		$log = new DBRecord( LOG_TBL );
		
		$log->logtime = date("Y-m-d H:i:s");
		$log->level = $level;
		$log->message = $message;
	}
	catch( Exception $e ) {
		// 
	}
}

function toTimestamp( $string )
{
	sscanf( $string, "%4d-%2d-%2d %2d:%2d:%2d", $y, $mon, $day, $h, $min, $s );
	return mktime( $h, $min, $s, $mon, $day, $y );
}

function toDatetime( $timestamp )
{
	return date("Y-m-d H:i:s", $timestamp);
}

function jdialog( $message, $url = "index.php" )
{
    header( "Content-Type: text/html;charset=utf-8" );
    exit( "<script type=\"text/javascript\">\n" .
          "<!--\n".
         "alert(\"". $message . "\");\n".
         "window.open(\"".$url."\",\"_self\");".
         "// -->\n</script>" );
}

// マルチバイトstr_replace
function mb_str_replace($search, $replace, $target, $encoding = "UTF-8" )
{
	$notArray = !is_array($target) ? TRUE : FALSE;
	$target = $notArray ? array($target) : $target;
	$search_len = mb_strlen($search, $encoding);
	$replace_len = mb_strlen($replace, $encoding);
	
	foreach ($target as $i => $tar) {
		$offset = mb_strpos($tar, $search);
		while ($offset !== FALSE){
			$tar = mb_substr($tar, 0, $offset).$replace.mb_substr($tar, $offset + $search_len);
			$offset = mb_strpos($tar, $search, $offset + $replace_len);
		}
		$target[$i] = $tar;
	}
	return $notArray ? $target[0] : $target;
}

/**
 * クラスのオートロード
 * @param string $className クラス名
 */
function custom_autoloader($className)
{
	$file_name = preg_replace('/[^a-z_A-Z0-9]/u', '', $className) . '.php';
	require_once $file_name;
}

function filesize_n($path)
{
	$size = @filesize($path);
	if( $size <= 0 ){
		ob_start();
		system('ls -al "'.$path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
		$size = ob_get_clean();
	}
	return human_filesize($size);
}

function human_filesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
?>