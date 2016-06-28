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
	 * @param array $GET_DATA GETデータ
	 * @param array $POST_DATA POSTデータ
	 * @return array
	 */
	public function getReserveData($GET_DATA, $POST_DATA)
	{
		$reserve_data = array();
		$sql = "SELECT a.*, b.name_en AS cat, c.name AS station_name";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL)." a";
		$sql .= " LEFT JOIN ".$this->getFullTblName(CATEGORY_TBL)." b";
		$sql .= "   ON a.category_id = b.id";
		$sql .= " LEFT JOIN ".$this->getFullTblName(CHANNEL_TBL)." c";
		$sql .= "   ON a.channel_id = c.id";
		$sql .= " WHERE a.complete = '0'";
		if ($GET_DATA['keyword_id'] != "")
			$sql .= " AND a.autorec = :key_id";
		else if ($POST_DATA['do_search'] != "")
		{
			if ($POST_DATA['search'] != "")
				$sql .= " AND CONCAT(title, description) LIKE :search";
			if ($POST_DATA['category_id'] != 0)
				$sql .= " AND category_id = :cate_id";
			if ($POST_DATA['station'] != 0)
				$sql .= " AND channel_id = :station";
		}
		$sql .= " ORDER BY starttime ASC";
		$stmt = $this->db->prepare($sql);
		if ($GET_DATA['keyword_id'] != "")
			$stmt->bindValue(':key_id', $GET_DATA['keyword_id']);
		else if ($POST_DATA['do_search'] != "")
		{
			if ($POST_DATA['search'] != "")
				$stmt->bindValue(':search', "%{$POST_DATA['search']}%");
			if ($POST_DATA['category_id'] != 0)
				$stmt->bindValue(':cate_id', $POST_DATA['category_id']);
			if ($POST_DATA['station'] != 0)
				$stmt->bindValue(':station', $POST_DATA['station']);
		}
		$stmt->execute();
		$reserve_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $reserve_data;
	}

	/**
	 * 録画済みデータ取得
	 * @param array $POST_DATA POSTデータ
	 * @return array
	 */
	public function getRecordedData($POST_DATA)
	{
		$recorded_data = array();
		$sql = "SELECT a.*, b.name_en AS cat, c.name AS station_name";
		$sql .= " FROM ".$this->getFullTblName(RESERVE_TBL)." a";
		$sql .= " LEFT JOIN ".$this->getFullTblName(CATEGORY_TBL)." b";
		$sql .= "   ON a.category_id = b.id";
		$sql .= " LEFT JOIN ".$this->getFullTblName(CHANNEL_TBL)." c";
		$sql .= "   ON a.channel_id = c.id";
		$sql .= " WHERE starttime < CURRENT_TIMESTAMP";
		if ($POST_DATA['do_search'] != "")
		{
			if ($POST_DATA['search'] != "")
				$sql .= " AND CONCAT(title, description) LIKE :search";
			if ($POST_DATA['category_id'] != 0)
				$sql .= " AND category_id = :cate_id";
			if ($POST_DATA['station'] != 0)
				$sql .= " AND channel_id = :station";
		}
		$sql .= " ORDER BY starttime DESC";
		$stmt = $this->db->prepare($sql);
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

	/**
	 * 録画完了とする
	 * @param int $reserve_id 予約ID
	 * @return array
	 */
	public function setRecordFinished($reserve_id)
	{
		$this->updateRow($this->getFullTblName(RESERVE_TBL), array('complete' => 1), array('id' => $reserve_id));
	}

	/**
	 * 予約を削除する
	 * @param int $reserve_id 予約ID
	 * @return array
	 */
	public function delReserveData($reserve_id)
	{
		$this->deleteRow($this->getFullTblName(RESERVE_TBL), array('id' => $reserve_id));
	}
}
?>