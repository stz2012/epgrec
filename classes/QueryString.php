<?php
/**
 * GET変数クラス
 * @package RequestVariables
 * @subpackage QueryString
 */
class QueryString extends RequestVariables
{
	/**
	 * (non-PHPdoc)
	 * @see RequestVariables::setValues()
	 */
	protected function setValues()
	{
		$GET_DATA = UtilString::parseQueryString($_SERVER['QUERY_STRING'], true);
		if (count($GET_DATA) == 0 && count($_GET) > 0)
			$GET_DATA = UtilString::getSanitizeData($_GET);
		foreach ($GET_DATA as $key => $value)
		{
			$this->_values[$key] = $value;
		}
	}
}
?>
