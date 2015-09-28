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

	/**
	 * 録画モード一覧取得
	 * @return array
	 */
	public function getRecModeOptions()
	{
		global $RECORD_MODE;
		$modes = array();
		foreach ( $RECORD_MODE as $key => $val )
			$modes[$key] = $val['mode'];
		return modes;
	}

	/**
	 * チューナー種別一覧取得
	 * @return array
	 */
	public function getTunerTypeOptions()
	{
		$types = array('*' => 'すべて');
		if ( $this->setting->gr_tuners != 0 )
			$types['GR'] = 'GR';
		if ( $this->setting->bs_tuners != 0 )
		{
			$types['BS'] = 'BS';
			if ($this->setting->cs_rec_flg != 0)
				$types['CS'] = 'CS';
		}
		return $types;
	}

	/**
	 * 局一覧取得
	 * @return array
	 */
	public function getStationOptions()
	{
		$stations = array(0 => 'すべて');
		$recs = $this->selectRow('*', "{$this->setting->tbl_prefix}".CHANNEL_TBL, '');
		foreach ( $recs as $r )
			$stations[$r['id']] = $r['name'];
		return $stations;
	}

	/**
	 * カテゴリ一覧出力
	 * @return array
	 */
	public function getCategoryOptions()
	{
		$categorys = array(0 => 'すべて');
		$recs = $this->selectRow('*', "{$this->setting->tbl_prefix}".CATEGORY_TBL, '');
		foreach ( $recs as $r )
			$categorys[$r['id']] = $r['name_jp'];
		return $categorys;
	}
}
?>