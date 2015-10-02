<?php
/**
 * epgrec - 録画番組モデルクラス
 * @package CommonModel
 * @subpackage RecprogModel
 */
class RecprogModel extends CommonModel
{
	/**
	 * 予約データ取得
	 * @return array
	 */
	public function getReserveData()
	{
		$reserve_data = array();
		$sql = "SELECT a.*, b.name_en AS cat, c.name AS station_name";
		$sql .= " FROM {$this->setting->tbl_prefix}".RESERVE_TBL." a";
		$sql .= " LEFT JOIN {$this->setting->tbl_prefix}".CATEGORY_TBL." b";
		$sql .= "   ON a.category_id = b.id";
		$sql .= " LEFT JOIN {$this->setting->tbl_prefix}".CHANNEL_TBL." c";
		$sql .= "   ON a.channel_id = c.id";
		$sql .= " WHERE a.complete = '0'";
		$sql .= " ORDER BY starttime ASC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$reserve_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $reserve_data;
	}

	/**
	 * 録画済みデータ取得
	 * @return array
	 */
	public function getRecordedData($POST_DATA)
	{
		$recorded_data = array();
		$sql = "SELECT a.*, b.name_en AS cat, c.name AS station_name";
		$sql .= " FROM {$this->setting->tbl_prefix}".RESERVE_TBL." a";
		$sql .= " LEFT JOIN {$this->setting->tbl_prefix}".CATEGORY_TBL." b";
		$sql .= "   ON a.category_id = b.id";
		$sql .= " LEFT JOIN {$this->setting->tbl_prefix}".CHANNEL_TBL." c";
		$sql .= "   ON a.channel_id = c.id";
		$sql .= " WHERE starttime < CAST(:starttime AS TIMESTAMP)";
		if ($POST_DATA['key'] != "")
			$sql .= " AND autorec = :autorec";
		if ($POST_DATA['do_search'] != "")
		{
			if ($POST_DATA['search'] != "")
				$sql .= " AND CONCAT(title,description) like :search";
			if ($POST_DATA['category_id'] != 0)
				$sql .= " AND category_id= :cate_id";
			if ($POST_DATA['station'] != 0)
				$sql .= " AND channel_id= :station";
		}
		$sql .= " ORDER BY starttime DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':starttime', date('Y-m-d H:i:s'));
		if ($POST_DATA['key'] != "")
			$stmt->bindValue(':autorec', $POST_DATA['key']);
		if ($POST_DATA['do_search'] != "")
		{
			if ($POST_DATA['search'] != "")
				$stmt->bindValue(':search', "%{$POST_DATA['search']}%");
			if ($POST_DATA['category_id'] != 0)
				$stmt->bindValue(':cate_id', $POST_DATA['category_id']);
			if ($POST_DATA['station'] != 0)
				$stmt->bindValue(':station', $POST_DATA['station']);
		}
		$stmt->execute();
		$recorded_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $recorded_data;
	}
}
?>