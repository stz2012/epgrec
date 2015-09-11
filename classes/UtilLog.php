<?php
/**
 * ログユーティリティ
 * @package Util
 * @subpackage UtilLog
 */
class UtilLog
{
	/**
	 * ログメッセージの保存
	 * @param string $msg メッセージ
	 * @param string prefix ファイル名プレフィックス
	 */
	public static function writeLog($msg, $prefix='ERROR')
	{
		// メッセージが配列やオブジェクトだった場合
		if (is_array($msg) || is_object($msg))
			$msg = print_r($msg, true);
		$file_name = '';
		$is_already = false;
		// ログファイルのパスが定義されている場合
		if (defined('LOG_FILEPATH'))
		{
			$dir_name = rtrim(LOG_FILEPATH, '/');
			// ディレクトリまたはファイルが存在する場合
			if (file_exists($dir_name))
			{
				// 定義されたパスがディレクトリで、書き込み可能の場合
				if (is_dir($dir_name) && is_writable($dir_name))
				{
					$file_name = $dir_name.'/'.strtolower($prefix).'_log';
					if (file_exists($file_name) && is_file($file_name) && is_writable($file_name))
						$is_already = true;
				}
				// 定義されたパスがファイルで、書き込み可能の場合
				else if (is_file($dir_name) && is_writable($dir_name))
				{
					$file_name = $dir_name;
					$is_already = true;
				}
			}
		}
		// ログファイルが出力可能の場合
		if ($file_name)
		{
			// 既にファイルが存在する場合
			if ($is_already)
			{
				// ファイルサイズチェック
				$size = filesize($file_name);
				if ($size > 1024000)
				{
					@copy($file_name, $file_name.'_'.date('YmdHis'));
					@unlink($file_name);
				}
			}
			$fp = fopen($file_name, "a");		// 書き込みでオープン
			if ($fp)
			{
				flock($fp, LOCK_EX);			// データロック
				if (array_key_exists('REMOTE_ADDR', $_SERVER) && array_key_exists('REQUEST_URI', $_SERVER))
					fputs($fp, date("[Y-m-d H:i:s]") . "[{$_SERVER['REMOTE_ADDR']}][" . ((isset($argv[0])) ? $argv[0] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . "]\n");
				else if (isset($argv[0]))
					fputs($fp, date("[Y-m-d H:i:s]") . "[{$argv[0]}]\n");	// コマンドライン起動時
				else
				{
					$dbg = debug_backtrace();
					fputs($fp, date("[Y-m-d H:i:s]") . "[{$dbg[1]['function']}]\n");
				}
				fputs($fp, $msg . "\n");
				flock($fp, LOCK_UN);			// データロック解除
				fclose($fp);
			}
		}
	}
}
?>