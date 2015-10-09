<?php
/**
 * epgrec - トップページモデルクラス
 * @package CommonModel
 * @subpackage IndexModel
 */
class IndexModel extends CommonModel
{
	/**
	 * ユーザデータ取得
	 * @param string $login_name ログイン名
	 * @param string $login_pass ログインパス
	 * @return array
	 */
	public function getUserData($login_name, $login_pass)
	{
		return $this->selectRow('*',
			$this->getFullTblName(USER_TBL),
			array('login_name' => $login_name, 'login_pass' => sha1($login_pass))
		);
	}

	/**
	 * 番組表データ取得
	 * @return array
	 */
	public function getProgramData($channel_type, $top_time, $last_time)
	{
		$program_data = array();
		$sql = "SELECT a.channel AS ch_channel, a.name AS ch_name,";
		$sql .= " a.channel_disc AS ch_disc, a.sid, a.skip, b.*,";
		$sql .= " c.name_en AS cate_name, COALESCE(d.rsv_cnt, 0) AS rec";
		$sql .= "  FROM ".$this->getFullTblName(CHANNEL_TBL)." a";
		$sql .= " INNER JOIN ".$this->getFullTblName(PROGRAM_TBL)." b";
		$sql .= "    ON b.channel_id = a.id";
		if (self::getDbType() == 'pgsql')
		{
			$sql .= " AND b.endtime > CAST(:top_time AS TIMESTAMP)";
			$sql .= " AND b.starttime < CAST(:last_time AS TIMESTAMP)";
		}
		else if (self::getDbType() == 'sqlite')
		{
			$sql .= " AND datetime(b.endtime) > datetime(:top_time)";
			$sql .= " AND datetime(b.starttime) < datetime(:last_time)";
		}
		else
		{
			$sql .= " AND b.endtime > CAST(:top_time AS DATETIME)";
			$sql .= " AND b.starttime < CAST(:last_time AS DATETIME)";
		}
		$sql .= "  LEFT JOIN ".$this->getFullTblName(CATEGORY_TBL)." c";
		$sql .= "    ON c.id = b.category_id";
		$sql .= "  LEFT JOIN (";
		$sql .= "    SELECT program_id, COUNT(*) AS rsv_cnt";
		$sql .= "      FROM ".$this->getFullTblName(RESERVE_TBL);
		$sql .= "     WHERE complete = '0'";
		if (self::getDbType() == 'pgsql')
		{
			$sql .= " AND endtime > CAST(:top_time AS TIMESTAMP)";
			$sql .= " AND starttime < CAST(:last_time AS TIMESTAMP)";
		}
		else if (self::getDbType() == 'sqlite')
		{
			$sql .= " AND datetime(endtime) > datetime(:top_time)";
			$sql .= " AND datetime(starttime) < datetime(:last_time)";
		}
		else
		{
			$sql .= " AND endtime > CAST(:top_time AS DATETIME)";
			$sql .= " AND starttime < CAST(:last_time AS DATETIME)";
		}
		$sql .= "     GROUP BY program_id";
		$sql .= "  ) d";
		$sql .= "    ON d.program_id = b.id";
		$sql .= " WHERE a.type = :channel_type";
		$sql .= "   AND a.id > 0";
		$sql .= " ORDER BY CAST(a.sid AS INTEGER), b.starttime";
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':channel_type', $channel_type);
		$stmt->bindValue(':top_time', toDatetime($top_time));
		$stmt->bindValue(':last_time', toDatetime($last_time));
		$stmt->execute();
		$program_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $program_data;
	}

	/**
	 * MediaTombデータ更新
	 * @param int $reserve_id ログイン名
	 * @param array $login_pass ログインパス
	 * @return array
	 */
	public function updMediaTombData($reserve_id, $upd_data)
	{
		$this->updateRow('mt_cds_object', $upd_data,
											array('metadata' => array(
													'operator' => 'regexp',
													   'value' => 'epgrec:id='.$reserve_id.'$')));
	}
}
?>