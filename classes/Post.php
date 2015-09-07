<?php
/**
 * POST変数クラス
 * @package RequestVariables
 * @subpackage Post
 */
class Post extends RequestVariables
{
	/**
	 * (non-PHPdoc)
	 * @see RequestVariables::setValues()
	 */
	protected function setValues()
	{
		$POST_DATA = UtilString::getSanitizeData($_POST);
		foreach ($POST_DATA as $key => $value)
		{
			$this->_values[$key] = $value;
		}
	}
}
?>
