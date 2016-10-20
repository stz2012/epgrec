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
	 * ユーザ情報設定
	 * @param int $user_id 
	 * @param array $user_data
	 */
	public function setUserInfo($user_id, $user_data)
	{
		unset($user_data['token']);
		$user_data['name'] = $user_data['user_name'];
		unset($user_data['user_name']);
		if ($user_data['login_pass'] != '')
			$user_data['login_pass'] = sha1($user_data['login_pass']);
		else
			unset($user_data['login_pass']);
		$this->updateRow($this->getFullTblName(USER_TBL), $user_data, array('id' => $user_id));
	}

	/**
	 * 録画中かどうか
	 * @return bool
	 */
	public function isRecordingNow()
	{
		$sql = "SELECT COUNT(id)";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= " WHERE complete <> '1'";
		if ($this->setting->db_type == 'sqlite')
		{
			$sql .= " AND datetime(starttime) <= datetime('now', 'localtime')";
			$sql .= " AND datetime(endtime) >= datetime('now', 'localtime')";
		}
		else
		{
			$sql .= " AND starttime <= now()";
			$sql .= " AND endtime >= now()";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$cnt = $stmt->fetchColumn();
		$stmt->closeCursor();
		return ($cnt > 0);
	}

	/**
	 * 何分以内に予約データが存在するかどうか
	 * @param int $minutes 
	 * @return bool
	 */
	public function isExistReservationWithInMinutes($minutes)
	{
		$sql = "SELECT COUNT(id)";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= " WHERE complete <> '1'";
		if ($this->setting->db_type == 'pgsql')
		{
			$sql .= " AND starttime >= now()";
			$sql .= " AND starttime <= (now() + INTERVAL '{$minutes} MINUTE')";
		}
		else if ($this->setting->db_type == 'sqlite')
		{
			$sql .= " AND datetime(starttime) >= datetime('now', 'localtime')";
			$sql .= " AND datetime(starttime) <= datetime('now', '+{$minutes} minutes', 'localtime')";
		}
		else
		{
			$sql .= " AND starttime >= now()";
			$sql .= " AND starttime <= (now() + INTERVAL {$minutes} MINUTE)";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$cnt = $stmt->fetchColumn();
		$stmt->closeCursor();
		return ($cnt > 0);
	}

	/**
	 * 何分以内の録画済データが存在するかどうか
	 * @param int $minutes 
	 * @return bool
	 */
	public function isExistRecordedWithInMinutes($minutes)
	{
		$sql = "SELECT COUNT(id)";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= " WHERE complete = '1'";
		if ($this->setting->db_type == 'pgsql')
		{
			$sql .= " AND endtime >= (now() - INTERVAL '{$minutes} MINUTE')";
			$sql .= " AND endtime <= now()";
		}
		else if ($this->setting->db_type == 'sqlite')
		{
			$sql .= " AND datetime(endtime) >= datetime('now', '-{$minutes} minutes', 'localtime')";
			$sql .= " AND datetime(endtime) <= datetime('now', 'localtime')";
		}
		else
		{
			$sql .= " AND endtime >= (now() - INTERVAL {$minutes} MINUTE)";
			$sql .= " AND endtime <= now()";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$cnt = $stmt->fetchColumn();
		$stmt->closeCursor();
		return ($cnt > 0);
	}

	/**
	 * 何分以内の予約時間を取得
	 * @param int $minutes 
	 * @return string
	 */
	public function getReserveTimeWithInMinutes($minutes)
	{
		$retval = '';
		$sql = "SELECT starttime";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= " WHERE complete <> '1'";
		if ($this->setting->db_type == 'pgsql')
		{
			$sql .= " AND starttime >= now()";
			$sql .= " AND starttime <= (now() + INTERVAL '{$minutes} MINUTE')";
		}
		else if ($this->setting->db_type == 'sqlite')
		{
			$sql .= " AND datetime(starttime) >= datetime('now', 'localtime')";
			$sql .= " AND datetime(starttime) <= datetime('now', '+{$minutes} minutes', 'localtime')";
		}
		else
		{
			$sql .= " AND starttime >= now()";
			$sql .= " AND starttime <= (now() + INTERVAL {$minutes} MINUTE)";
		}
		$sql .= " ORDER BY starttime";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchColumn();
		if ($result !== false)
			$retval = $result;
		$stmt->closeCursor();
		return $retval;
	}

	/**
	 * 直近の予約時間の何分前を取得
	 * @param int $minutes 
	 * @return string
	 */
	public function getImmediateReserveTimeBeforeMinutes($minutes)
	{
		$retval = '';
		$sql = "SELECT";
		if ($this->setting->db_type == 'pgsql')
			$sql .= " (starttime - INTERVAL '{$minutes} MINUTE') AS waketime";
		else if ($this->setting->db_type == 'sqlite')
			$sql .= " datetime(starttime, '-{$minutes} minutes') AS waketime";
		else
			$sql .= " (starttime - INTERVAL {$minutes} MINUTE) AS waketime";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= " WHERE complete <> '1'";
		if ($this->setting->db_type == 'sqlite')
			$sql .= " AND datetime(starttime) >= datetime('now', 'localtime')";
		else
			$sql .= " AND starttime >= now()";
		$sql .= " ORDER BY starttime";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchColumn();
		if ($result !== false)
			$retval = $result;
		$stmt->closeCursor();
		return $retval;
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