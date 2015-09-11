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
		foreach ($GET_DATA as $key => $value)
		{
			$this->_values[$key] = $value;
		}
	}
}
?>
