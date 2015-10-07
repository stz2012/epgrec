<?php
/**
 * 入力値検証クラス
 * @package ValidateBase
 * @subpackage Validate
 */
class Validate extends ValidateBase
{
	/**
	 * @var array エラー情報
	 */
	private $error_msg = array();
	
	
	/**
	 * ログイン検証
	 * @param array $POST_DATA POSTデータ
	 * @return array エラー情報
	 */
	public function user_login($POST_DATA)
	{
		$this->error_msg = array();
		// ログイン名
		if (self::trim_space($POST_DATA['login_name']) == "")
		{
			$this->error_msg[] = array(
				'type' => 'login_name',
				'message' => "ログイン名を入力してください。",
			);
		}
		else if (!self::valid_alnumsig($POST_DATA['login_name']))
		{
			$this->error_msg[] = array(
				'type' => 'login_name',
				'message' => "ログイン名は半角英数字で入力してください。",
			);
		}
		// パスワード
		if (self::trim_space($POST_DATA['passwd']) == "")
		{
			$this->error_msg[] = array(
				'type' => 'passwd',
				'message' => "パスワードを入力してください。",
			);
		}
		return $this->error_msg;
	}
}
?>