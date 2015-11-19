<?php
/**
 * SESSION変数クラス
 * @package RequestVariables
 * @subpackage Session
 */
class Session extends RequestVariables
{
	/**
	 * (non-PHPdoc)
	 * @see RequestVariables::setValues()
	 */
	protected function setValues()
	{
		session_start();
		session_regenerate_id();
		//$SESS_DATA = UtilString::getSanitizeData($_SESSION);
		//foreach ($SESS_DATA as $key => $value)
		foreach ($_SESSION as $key => $value)
		{
			$this->_values[$key] = $value;
		}
	}

	/**
	 * セッションIDの設定
	 * @param string $new_id 設定するID
	 */
	public function setId($new_id)
	{
		session_write_close();
		session_id($new_id);
		session_start();
	}

	/**
	 * セッションデータの保存
	 * @param string $key 保存するキー
	 */
	public function save($key)
	{
		$_SESSION[$key] = $this->_values[$key];
	}
}
?>
