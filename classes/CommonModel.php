<?php
/**
 * 共通モデルクラス
 * @package ModelBase
 * @subpackage CommonModel
 */
class CommonModel extends ModelBase
{
	/**
	 * @var array 設定情報
	 */
	protected $setting;

	/**
	 * 設定情報を設定
	 * @param array $setting
	 */
	public function setSetting($setting)
	{
		$this->setting = $setting;
	}
}
?>