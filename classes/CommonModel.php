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
	 * 実テーブル名を取得
	 * @param string $table 
	 * @return string
	 */
	public function getFullTblName($table)
	{
		return $this->setting->tbl_prefix.$table;
	}

	/**
	 * カテゴリ一覧取得
	 * @return array
	 */
	public function getCategoryData()
	{
		return $this->selectRow('*', $this->getFullTblName(CATEGORY_TBL), '', 'id');
	}

	/**
	 * ログ一覧取得
	 * @return array
	 */
	public function getLogList()
	{
		return $this->selectRow('*', $this->getFullTblName(LOG_TBL), '', 'logtime DESC');
	}

	/**
	 * ユーザ一覧取得
	 * @return array
	 */
	public function getUserList()
	{
		$ret = array();
		$recs = $this->selectRow('*', $this->getFullTblName(USER_TBL), '', 'id');
		foreach ( $recs as $r )
		{
			$param = array();
			$param['user_id'] = $r['id'];
			$r['link'] = UtilString::buildQueryString($param);
			array_push( $ret, $r );
		}
		return $ret;
	}

	/**
	 * ユーザ情報取得
	 * @param int $user_id 
	 * @return array
	 */
	public function getUserInfo($user_id)
	{
		$recs = $this->selectRow('*', $this->getFullTblName(USER_TBL), array('id' => $user_id));
		$recs[0]['login_pass'] = '';
		return $recs[0];
	}

	/**
	 * PDOドライバ一覧取得
	 * @return array
	 */
	public function getPdoDriverOptions()
	{
		$ret = array();
		$drivers = PDO::getAvailableDrivers();
		foreach ( $drivers as $val )
		{
			switch ( $val )
			{
				case 'mysql':
					$ret[$val] = 'MySQL';
					break;
				case 'pgsql':
					$ret[$val] = 'PostgreSQL';
					break;
				case 'sqlite':
					$ret[$val] = 'SQLite';
					break;
			}
		}
		return $ret;
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
			$modes[$key] = $val['name'];
		return $modes;
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
	 * TV局一覧取得
	 * @return array
	 */
	public function getStationOptions()
	{
		$stations = array(0 => 'すべて');
		$recs = $this->selectRow('*', $this->getFullTblName(CHANNEL_TBL), '');
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
		$recs = $this->selectRow('*', $this->getFullTblName(CATEGORY_TBL), '');
		foreach ( $recs as $r )
			$categorys[$r['id']] = $r['name_jp'];
		return $categorys;
	}
}
?>