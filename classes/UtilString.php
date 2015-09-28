<?php
/**
 * 文字列ユーティリティ
 * @package Util
 * @subpackage UtilString
 */
class UtilString
{
	/**
	 * QUERY_STRING生成関数
	 * @param array $value QUERY情報配列
	 * @param bool $AddRandom ランダム値を付加するかどうか
	 * @return string QUERY文字列
	 */
	public static function buildQueryString($value, $AddRandom=true)
	{
		$result = '';
		if (is_array($value))
		{
			if ($AddRandom)
				$value = array_merge(array('rand_key' => time()), $value);
			$query = http_build_query($value);
			$ret = self::getEncryptString($query);
			if (!PEAR::isError($ret))
				return $ret;
		}
		return $result;
	}
	
	/**
	 * QUERY_STRING解析関数
	 * @param array $value QUERY文字列
	 * @param bool $CheckSess セッションチェックをするかどうか
	 * @return string QUERY情報配列
	 */
	public static function parseQueryString($value, $CheckSess=false)
	{
		$result = array();
		if ($value != "")
		{
			$ret = self::getDecryptString($value);
			if (!PEAR::isError($ret))
			{
				parse_str($ret, $result);
				if (isset($result['rand_key']))
				{
					if ($CheckSess)
					{
						if (time() > strtotime(SESS_TIMEOUT, (int)$result['rand_key']))
							$result['SESS_ERROR'] = "セッションの有効期限が切れました。";
					}
					unset($result['rand_key']);
				}
			}
		}
		return $result;
	}
	
	/**
	 * 暗号化関数（Blowfish使用）
	 * @param string  $value 生文字列
	 * @return string 暗号化文字列
	 */
	public static function getEncryptString($value, $cryptKey=null)
	{
		try
		{
			$size = mcrypt_get_block_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
			$input = self::_pkcs5_pad($value, $size);
			$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_ECB, '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
			mcrypt_generic_init($td, ($cryptKey != null) ? $cryptKey : CRYPT_KEY, $iv);
			$data = mcrypt_generic($td, $input);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$data = pack('H*', bin2hex($data));
			return base64_encode( $data );
		}
		catch (Exception $e)
		{
			return PEAR::raiseError('Error in getEncryptString');
		}
	}
	
	/**
	 * 復号化関数（Blowfish使用）
	 * @param string  $value 暗号化文字列
	 * @return string 生文字列
	 */
	public static function getDecryptString($value, $cryptKey=null)
	{
		try
		{
			$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_ECB, '');
			$data = pack("H*" , bin2hex(base64_decode($value)));
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
			mcrypt_generic_init($td, ($cryptKey != null) ? $cryptKey : CRYPT_KEY, $iv);
			$data = mdecrypt_generic($td, $data);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$data = self::_pkcs5_unpad($data);
			return $data;
		}
		catch (Exception $e)
		{
			return PEAR::raiseError('Error in getDecryptString');
		}
	}
	
	/**
	 * サニタイジング関数
	 * @param array $param サニタイジングする配列
	 * @return array 変換後の配列
	 */
	public static function getSanitizeData($param)
	{
		if (is_array($param))
		{
			$ret_arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
					$ret_arr[$key] = self::getSanitizeData($value);
				else
					$ret_arr[$key] = htmlspecialchars($value, ENT_QUOTES);
			}
			return $ret_arr;
		}
		else
			return htmlspecialchars($param, ENT_QUOTES);
	}
	
	/**
	 * アンサニタイジング関数
	 * @param array $param サニタイジングされた配列
	 * @return array 変換後の配列
	 */
	public static function getUnsanitizeData($param)
	{
		if (is_array($param))
		{
			$ret_arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
					$ret_arr[$key] = self::getUnsanitizeData($value);
				else
					$ret_arr[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
			}
			return $ret_arr;
		}
		else
			return htmlspecialchars_decode($param, ENT_QUOTES);
	}
	
	/**
	 * SJISへ文字コード変換する
	 * @param mixed $param 文字コード変換する値／配列
	 * @param string $encode 変換元文字エンコード
	 * @return mixed 変換後のデータ
	 */
	public static function getSjisString($param, $encode='UTF-8')
	{
		if (is_array($param))
		{
			$ret_arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
					$ret_arr[$key] = self::getSjisString($value, $encode);
				else
					$ret_arr[$key] = mb_convert_encoding($value, 'SJIS-win', $encode);
			}
			return $ret_arr;
		}
		else
			return mb_convert_encoding($param, 'SJIS-win', $encode);
	}
	
	/**
	 * UTF-8へ文字コード変換する
	 * @param mixed $param 文字コード変換する値／配列
	 * @param string $encode 変換元文字エンコード
	 * @return mixed 変換後のデータ
	 */
	public static function getUtf8String($param, $encode='SJIS-win')
	{
		if (is_array($param))
		{
			$ret_arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
					$ret_arr[$key] = self::getUtf8String($value, $encode);
				else
					$ret_arr[$key] = mb_convert_encoding($value, 'UTF-8', $encode);
			}
			return $ret_arr;
		}
		else
			return mb_convert_encoding($param, 'UTF-8', $encode);
	}
	
	/**
	 * ランダムな文字列を生成
	 * @param int $nLengthRequired 必要な文字列長。省略すると 8 文字
	 * @return string ランダムな文字列
	 */
	public static function getRandomString($nLengthRequired = 8)
	{
		$sCharList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
		mt_srand(microtime()*100000);
		$sRes = "";
		for($i = 0; $i < $nLengthRequired; $i++)
			$sRes .= $sCharList{mt_rand(0, strlen($sCharList) - 1)};
		return $sRes;
	}

	private static function _pkcs5_pad($text, $blocksize)
	{
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	private static function _pkcs5_unpad($text)
	{
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}
}
?>