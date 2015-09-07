<?php
/**
 * 日付ユーティリティ
 * @package Util
 * @subpackage UtilDate
 */
class UtilDate
{
	/**
	 * 西暦年月日を和暦年月日に変換する
	 * @param string $strYmd 西暦の年月日
	 * @param string $encoding 文字エンコーディング
	 * @return string 和暦年月日(OK) / NULL(NG)
	 * @throws Exception
	 */
	public static function getJapaneseCalendar($strYmd, $encoding='UTF-8')
	{
		try
		{
			$judgeNengos = self::getNengos();
			
			// 空白文字類を半角スペースに変換
			$strYmd = preg_replace('/\s/is', ' ', $strYmd);
			if ($strYmd === FALSE)
			{
				throw new Exception('空白文字類の置換に失敗しました。');
			}
			
			// s：「全角」スペースを「半角」に変換
			$strYmd = mb_convert_kana($strYmd, 's', $encoding);
			
			// 数字以外を半角スペースに変換
			$strYmd = preg_replace('/[^\d]+/', ' ', $strYmd);
			if ($strYmd === FALSE)
			{
				throw new Exception('数字以外の置換に失敗しました。');
			}
			
			// 日付の妥当性を検証
			list($year, $month, $day) = sscanf($strYmd, '%s %s %s');
			if (! checkdate($month, $day, $year))
			{
				throw new Exception('不正な年月日です。');
			}
			
			// 2桁0埋め
			$month = sprintf('%02d', $month);
			$day = sprintf('%02d', $day);
			
			$ymd = "{$year}-{$month}-{$day}";
			$nengo = '';
			$bMatch = FALSE;
			foreach ($judgeNengos as $nengos)
			{
				if ($nengos['start'] <= $ymd && $ymd <= $nengos['end'])
				{
					$nengo  = $nengos['nengo'];
					$year  -= $nengos['baseYear'];
					$bMatch = TRUE;
					break;
				}
			}
			if (! $bMatch)
			{
				throw new Exception('範囲外の年月日です。');
			}
			
			return "{$nengo}-{$year}-{$month}-{$day}";
		}
		catch (Exception $e)
		{
			// 例外メッセージを出力
			return $e->getMessage();
		}
	}

	/**
	 * 年号名称一覧を取得
	 * @return array
	 */
	public static function getJapaneseEraNames()
	{
		$judgeNengos = self::getNengos();
		$nengo = array();
		foreach ($judgeNengos as $nengos)
		{
			$nengo[$nengos['nengo']] = $nengos['nengo'];
		}
		return $nengo;
	}

	/**
	 * 年号年数一覧を取得
	 * @return array
	 */
	public static function getJapaneseEraYears()
	{
		$year = array();
		foreach (range(1, 64) as $value)
		{
			$year[$value] = $value;
		}
		$year[1] = '元年';
		return $year;
	}

	/**
	 * 和暦年月日を西暦年月日に変換する
	 * @param string $strYmd 和暦の年月日
	 * @param string $encoding 文字エンコーディング
	 * @return string 西暦年月日(OK) / NULL(NG)
	 * @throws Exception
	 */
	public static function getWesternCalendar($strYmd, $encoding = 'UTF-8')
	{
		try
		{
			$judgeNengos = self::getNengos();
			$judgeNengos['m'] =& $judgeNengos['明治'];
			$judgeNengos['t'] =& $judgeNengos['大正'];
			$judgeNengos['s'] =& $judgeNengos['昭和'];
			$judgeNengos['h'] =& $judgeNengos['平成'];
			
			// 元年を1年に変換
			$strYmd = str_replace('元', '1', $strYmd);
			
			// 空白文字類を半角スペースに変換
			$strYmd = preg_replace('/\s/is', ' ', $strYmd);
			if ($strYmd === FALSE)
			{
				throw new Exception('空白文字類の置換に失敗しました。');
			}
			
			// a：「全角」英数字を「半角」に変換
			// s：「全角」スペースを「半角」に変換
			$strYmd = mb_convert_kana($strYmd, 'as', $encoding);
			
			// 大文字を小文字に変換
			$strYmd = strtolower($strYmd);
			
			// 年号部分が存在しない場合
			$matches = NULL;
			if (! preg_match('/明治|大正|昭和|平成|m|t|s|h/is', $strYmd, $matches))
			{
				throw new Exception('未定義の年号です。');
			}
			// 年号
			$nengo = $matches[0];
			if (! array_key_exists($nengo, $judgeNengos))
			{
				throw new Exception('不正な年号です。');
			}
			
			// 年号部分を削除
			$strYmd = str_replace($nengo, '', $strYmd);
			// 数字以外を半角スペースに変換
			$strYmd = preg_replace('/[^\d]+/', ' ', $strYmd);
			if ($strYmd === FALSE)
			{
				throw new Exception('数字以外の置換に失敗しました。');
			}
			
			list($wareki, $month, $day) = sscanf($strYmd, '%s %s %s');
			// 2桁0埋め
			$month = sprintf('%02d', $month);
			$day = sprintf('%02d', $day);
			
			if (! preg_match('/\d{1,2}/', $wareki))
			{
				throw new Exception('不正な和歴です。');
			}
			
			if ($wareki <= 0)
			{
				throw new Exception('年は1以上を指定してください。');
			}
			
			$judgeNengo = $judgeNengos[$nengo];
			// 西暦変換
			$year = $wareki + $judgeNengo['baseYear'];
			
			$ymd = "$year-$month-$day";
			$bMatch = FALSE;
			foreach ($judgeNengos as $nengos)
			{
				if ($nengos['start'] <= $ymd && $ymd <= $nengos['end'])
				{
					$bMatch = TRUE;
					break;
				}
			}
			if (! $bMatch)
			{
				throw new Exception('範囲外の年月日です。');
			}
			// 日付の妥当性を検証
			if (! checkdate($month, $day, $year))
			{
				throw new Exception('不正な年月日です。');
			}
			
			return $ymd;
		}
		catch (Exception $e)
		{
			// 例外メッセージを出力
			return $e->getMessage();
		}
	}

	/**
	 * 曜日名を取得
	 * @param int $wday 0 (日曜) から 6 (土曜)
	 * @param bool $decorate カッコ付きで表示するか
	 * @param bool $fullname ～曜日で表示するか
	 * @return string フォーマットされた曜日名
	 */
	public static function getWeekdayName($wday, $decorate=false, $fullname=false)
	{
		$WNames = array('日','月','火','水','木','金','土');
		if ($decorate)
		{
			if ($fullname)
				return "({$WNames[$wday]}曜日)";
			else
				return "({$WNames[$wday]})";
		}
		else
		{
			if ($fullname)
				return "{$WNames[$wday]}曜日";
			else
				return "{$WNames[$wday]}";
		}
	}

	/**
	 * 年号配列を取得
	 * @return array
	 */
	private function getNengos()
	{
		$Nengos = array(
			'明治' => array('nengo' => '明治', 'start' => '1868-09-08', 'end' => '1912-07-29', 'baseYear' => 1867),
			'大正' => array('nengo' => '大正', 'start' => '1912-07-30', 'end' => '1926-12-24', 'baseYear' => 1911),
			'昭和' => array('nengo' => '昭和', 'start' => '1926-12-25', 'end' => '1989-01-07', 'baseYear' => 1925),
			'平成' => array('nengo' => '平成', 'start' => '1989-01-08', 'end' => '9999-12-31', 'baseYear' => 1988)
		);
		return $Nengos;
	}
}
?>