<?php
/**
 * 検証基底クラス
 * @package ValidateBase
 */
abstract class ValidateBase
{
	/**
	 * 空白除去
	 * @param string $text 対象文字列
	 * @return string 空白を除去した文字列
	 */
	public static function trim_space($text)
	{
		$text = preg_replace('/^[ 　]+/u', '', $text);
		$text = preg_replace('/[ 　]+$/u', '', $text);
		return $text;
	}

	/**
	 * 文字列長検証
	 * @param string $text 対象文字列
	 * @param int $param_len 指定文字列長
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_strlength($text, $param_len)
	{
		$str_len = mb_strlen($text, 'UTF-8');
		if ($str_len == $param_len)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 文字列長範囲検証
	 * @param string $text 対象文字列
	 * @param int $len1 指定文字列長１
	 * @param int $len2 指定文字列長２
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_strlength_range($text, $len1, $len2=1)
	{
		$str_len = mb_strlen($text, 'UTF-8');
		$len_min = ($len2 < $len1) ? $len2 : $len1;
		$len_max = ($len2 > $len1) ? $len2 : $len1;
		if ($str_len >= $len_min && $str_len <= $len_max)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 文字列幅検証
	 * @param string $text 対象文字列
	 * @param int $param_len 指定文字列幅
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_strwidth($text, $param_len)
	{
		$str_len = mb_strwidth($text, 'UTF-8');
		if ($str_len == $param_len)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 文字列幅範囲検証
	 * @param string $text 対象文字列
	 * @param int $len1 指定文字列幅１
	 * @param int $len2 指定文字列幅２
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_strwidth_range($text, $len1, $len2=1)
	{
		$str_len = mb_strwidth($text, 'UTF-8');
		$len_min = ($len2 < $len1) ? $len2 : $len1;
		$len_max = ($len2 > $len1) ? $len2 : $len1;
		if ($str_len >= $len_min && $str_len <= $len_max)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 数字検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_num($text)
	{
		if (preg_match("/^[0-9]+$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 数字範囲検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_num_range($text, $val1, $val2)
	{
		if (self::valid_num($text))
		{
			$val_min = ($val2 < $val1) ? $val2 : $val1;
			$val_max = ($val2 > $val1) ? $val2 : $val1;
			if (intval($text) >= $val_min && intval($text) <= $val_max)
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}

	/**
	 * 英数字検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_alnum($text)
	{
		if (preg_match("/^[a-zA-Z0-9]+$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * ひらがな検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_hiragana($text)
	{
		if (preg_match("/^[ぁ-ん]+$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * カタカナ検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_katakana($text)
	{
		if (preg_match("/^[ァ-ヶー]+$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 英数字記号検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_alnumsig($text)
	{
		if (preg_match("/^[!-~]+$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 郵便番号検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_postno($text)
	{
		if (preg_match("/^\d{3}\-\d{4}$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 電話番号検証
	 * @see https://github.com/sakatam/a-better-jp-phone-regex
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_telno($text)
	{
		if (preg_match("/^(0([1-9]{1}-?[1-9]\d{3}|[1-9]{2}-?\d{3}|[1-9]{2}\d{1}-?\d{2}|[1-9]{2}\d{2}-?\d{1})-?\d{4}|0[789]0-?\d{4}-?\d{4}|050-?\d{4}-?\d{4})$/u", $text))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * 日付文字列検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_date($text)
	{
		if (preg_match("/^\d{4}[-\/]+\d{1,2}[-\/]+\d{1,2}$/u", $text))
		{
			list($yyyy, $mm, $dd) = preg_split("/[-\/]/u", $text);
			if (checkdate($mm, $dd, $yyyy))
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}

	/**
	 * メールアドレス検証
	 * @param string $text 対象文字列
	 * @return bool TRUE:一致, FALSE:不一致
	 */
	public static function valid_mailaddr($text)
	{
		if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/u", $text))
		{
			list($username, $domain) = split('@', $text);
			if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
}
?>