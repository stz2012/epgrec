<?php
/**
 * epgrec - トップページモデルクラス
 * @package CommonModel
 * @subpackage IndexModel
 */
class IndexModel extends CommonModel
{
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
		$sql .= "  FROM {$this->setting->tbl_prefix}".CHANNEL_TBL." a";
		$sql .= " INNER JOIN {$this->setting->tbl_prefix}".PROGRAM_TBL." b";
		$sql .= "    ON b.channel_disc = a.channel_disc";
		$sql .= "   AND b.endtime > :top_time";
		$sql .= "   AND b.starttime < :last_time";
		$sql .= "  LEFT JOIN {$this->setting->tbl_prefix}".CATEGORY_TBL." c";
		$sql .= "    ON c.id = b.category_id";
		$sql .= "  LEFT JOIN (";
		$sql .= "    SELECT program_id, complete, COUNT(*) AS rsv_cnt";
		$sql .= "      FROM {$this->setting->tbl_prefix}".RESERVE_TBL;
		$sql .= "     WHERE endtime > :top_time";
		$sql .= "       AND starttime < :last_time";
		$sql .= "  ) d";
		$sql .= "    ON d.program_id = b.id";
		$sql .= "   AND d.complete = '0'";
		$sql .= " WHERE a.type = :channel_type";
		$sql .= "   AND a.id > 0";
		$sql .= " ORDER BY a.sid+0, b.starttime";
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':channel_type', $channel_type);
		$stmt->bindValue(':top_time', toDatetime($top_time));
		$stmt->bindValue(':last_time', toDatetime($last_time));
		$stmt->execute();
		$program_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $program_data;
	}
}
?>