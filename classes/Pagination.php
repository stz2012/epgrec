<?php
/**
 * ページングクラス
 * @package Pagination
 */
class Pagination
{
	/**
	 * @var array ページデータ
	 */
	private $page_data;

	/**
	 * ページデータ初期化
	 * @param int $data_cnt 表示データ件数
	 * @param int $now_page 現在のページ
	 */
	public function initialize($data_cnt, $now_page=0)
	{
		$this->page_data = array();
		$this->page_data['total'] = $data_cnt;
		// 現在ページの設定
		if ($now_page > 0)
			$this->page_data['cur_page'] = $now_page;
		else
			$this->page_data['cur_page'] = 1;
		// １ページあたりの最大表示数以内の場合
		if ($this->page_data['total'] <= NUM_BY_PAGE)
		{
			$this->page_data['rec_start']   = ($this->page_data['total'] == 0) ? 0 : 1;
			$this->page_data['rec_end']     = $this->page_data['total'];
			$this->page_data['slide_prev']  = 0;
			$this->page_data['slide_start'] = 1;
			$this->page_data['slide_end']   = 1;
			$this->page_data['slide_next']  = 0;
			$this->page_data['page_max']    = 1;
		}
		else
		{
			// 現在の件数位置（X～X 件）
			$this->page_data['rec_start'] = 1 + NUM_BY_PAGE * ($this->page_data['cur_page'] - 1);
			$this->page_data['rec_end'] = NUM_BY_PAGE * $this->page_data['cur_page'];
			if ($this->page_data['rec_end'] > $this->page_data['total'])
				$this->page_data['rec_end'] = $this->page_data['total'];
			
			// 最大ページ数算出
			if ($this->page_data['total'] % NUM_BY_PAGE == 0)
				$this->page_data['page_max'] = $this->page_data['total'] / NUM_BY_PAGE;
			else
				$this->page_data['page_max'] = intval($this->page_data['total'] / NUM_BY_PAGE) + 1;
			
			$pagebtn_h = floor(MAX_PAGE_BTN / 2);
			// 始点ページの算出
			if ($this->page_data['cur_page'] - $pagebtn_h > 0)
				$this->page_data['slide_start'] = $this->page_data['cur_page'] - $pagebtn_h;
			else
				$this->page_data['slide_start'] = 1;
			// 終点ページの算出
			if ($this->page_data['slide_start'] > 1)
				$this->page_data['slide_end'] = $this->page_data['cur_page'] + $pagebtn_h;
			else
				$this->page_data['slide_end'] = MAX_PAGE_BTN;
			// 終点ページの再計算(終点ページ＞最大ページはＮＧ)
			if ($this->page_data['slide_end'] > $this->page_data['page_max'])
				$this->page_data['slide_end'] = $this->page_data['page_max'];
			// 始点ページの再計算(最大ページ≦終点ページの場合は最大ページから再計算)
			if ($this->page_data['page_max'] <= $this->page_data['slide_end'])
				$this->page_data['slide_start'] = $this->page_data['page_max'] - (MAX_PAGE_BTN - 1);
			// 始点ページの再計算(始点ページ≦０はＮＧ)
			if ($this->page_data['slide_start'] <= 0)
				$this->page_data['slide_start'] = 1;
			
			// prev･next
			$this->page_data['slide_prev'] = ($this->page_data['cur_page'] > 1) ? $this->page_data['cur_page'] - 1 : 0;
			$this->page_data['slide_next'] = ($this->page_data['cur_page'] < $this->page_data['page_max']) ? $this->page_data['cur_page'] + 1 : 0;
		}
		return $this->page_data;
	}
	
	/**
	 * LIMIT句用配列取得
	 * @return array 開始レコード位置、表示件数
	 */
	public function getLimitArray()
	{
		return array(($this->page_data['total'] == 0) ? 0 : $this->page_data['rec_start']-1, NUM_BY_PAGE);
	}
	
	/**
	 * ボタンリンク取得
	 * @param array $extra_param ボタンリンクへの追加パラメータ
	 * @return array 生成したボタンリンク配列
	 */
	public function getBtnLink($extra_param=array())
	{
		$ret_arr = array();
		$ret_arr['cur_page'] = $this->page_data['cur_page'];
		
		// 前へボタン
		if ($this->page_data['slide_prev'] == 0)
			$ret_arr['slide_prev_link'] = "";
		else
			$ret_arr['slide_prev_link'] = UtilString::buildQueryString(array('cur_page' => $this->page_data['slide_prev']) + $extra_param);
		
		// 次へボタン
		if ($this->page_data['slide_next'] == 0)
			$ret_arr['slide_next_link'] = "";
		else
			$ret_arr['slide_next_link'] = UtilString::buildQueryString(array('cur_page' => $this->page_data['slide_next']) + $extra_param);
		
		// ページングバー表示用配列構築
		$ret_arr['slide_array'] = array();
		for ($i=$this->page_data['slide_start']; $i<=$this->page_data['slide_end']; $i++)
		{
			$ret_arr['slide_array'][] = array(
												'page_no'   => $i,
												'page_link' => UtilString::buildQueryString(array('cur_page' => $i) + $extra_param)
												);
		}
		return $ret_arr;
	}
}
?>