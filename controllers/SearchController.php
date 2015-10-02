<?php
/**
 * epgrec - 検索ページコントローラ
 * @package CommonController
 * @subpackage SearchController
 */
class SearchController extends CommonController
{
	/**
	 * デフォルト表示
	 */
	public function indexAction()
	{
		$search = "";
		$use_regexp = 0;
		$type = "*";
		$category_id = 0;
		$channel_id = 0;
		$weekofday = 0;
		$prgtime = 24;

		// パラメータの処理
		if ( $this->request->getPost('do_search') )
		{
			if ( $this->request->getPost('search') )
			{
				$search = $this->request->getPost('search');
				if ( $this->request->getPost('use_regexp') )
					$use_regexp = (int)($this->request->getPost('use_regexp'));
			}
			if ( $this->request->getPost('type') )
				$type = $this->request->getPost('type');
			if ( $this->request->getPost('station') )
				$channel_id = (int)($this->request->getPost('station'));
			if ( $this->request->getPost('category_id') )
				$category_id = (int)($this->request->getPost('category_id'));
			if ( $this->request->getPost('prgtime') )
				$prgtime = (int)($this->request->getPost('prgtime'));
			if ( $this->request->getPost('weekofday') )
				$weekofday = (int)($this->request->getPost('weekofday'));
		}

		$do_keyword = 0;
		if ( ($search != "") || ($type != "*") || ($category_id != 0) || ($channel_id != 0) )
			$do_keyword = 1;
		$programs = Reservation::getSearchData( $search, $use_regexp, $type, $channel_id, $category_id, $prgtime, $weekofday );

		$this->view->assign( "sitetitle",     "番組検索" );
		$this->view->assign( "do_keyword",    $do_keyword );
		$this->view->assign( "search" ,       $search );
		$this->view->assign( "use_regexp",    $use_regexp );
		$this->view->assign( "types",         $this->model->getTunerTypeOptions() );
		$this->view->assign( "sel_type",      $type );
		$this->view->assign( "stations",      $this->model->getStationOptions() );
		$this->view->assign( "sel_station",   $channel_id );
		$this->view->assign( "categorys",     $this->model->getCategoryOptions() );
		$this->view->assign( "sel_category",  $category_id );
		$this->view->assign( "prgtimes",      $this->_getPrgTimes() );
		$this->view->assign( "sel_prgtime",   $prgtime );
		$this->view->assign( "weekofdays",    $this->_getWeekOfDays() );
		$this->view->assign( "sel_weekofday", $weekofday );
		$this->view->assign( "programs",      $programs );
		$this->view->assign( "record_mode" ,  $this->model->getRecModeOptions() );
		$this->view->assign( "sel_recmode",   $this->setting->autorec_mode );
	}

	/**
	 * 自動録画キーワードの管理
	 */
	public function keywordAction()
	{
		global $RECORD_MODE;
		$stations = $this->model->getStationOptions();
		$categorys = $this->model->getCategoryOptions();
		$prgtimes = $this->_getPrgTimes();
		$weekofdays = $this->_getWeekOfDays();

		// 新規キーワードがポストされた
		if ( $this->request->getPost('add_keyword') == 1 )
		{
			try
			{
				$rec = new DBRecord( KEYWORD_TBL );
				$rec->keyword      = $this->request->getPost('k_search');
				$rec->use_regexp   = $this->request->getPost('k_use_regexp');
				$rec->type         = $this->request->getPost('k_type');
				$rec->channel_id   = $this->request->getPost('k_station');
				$rec->category_id  = $this->request->getPost('k_category');
				$rec->prgtime      = $this->request->getPost('k_prgtime');
				$rec->weekofday    = $this->request->getPost('k_weekofday');
				$rec->autorec_mode = $this->request->getPost('autorec_mode');
				$rec->update();

				// 一気に録画予約
				Reservation::keyword( $rec->id );
			}
			catch ( Exception $e )
			{
				exit( $e->getMessage() );
			}
		}

		$keywords = array();
		try
		{
			$recs = DBRecord::createRecords( KEYWORD_TBL );
			foreach ( $recs as $rec )
			{
				$arr = array();
				$arr['id']           = $rec->id;
				$arr['keyword']      = $rec->keyword;
				$arr['use_regexp']   = $rec->use_regexp;
				$arr['type']         = ( $rec->type == "*" ) ? "すべて" : $rec->type;
				$arr['channel']      = $stations["$rec->channel_id"];
				$arr['category']     = $categorys["$rec->category_id"];
				$arr['prgtime']      = $prgtimes["$rec->prgtime"];
				$arr['weekofday']    = $weekofdays["$rec->weekofday"];
				$arr['autorec_mode'] = $RECORD_MODE[(int)$rec->autorec_mode]['name'];
				array_push( $keywords, $arr );
			}
		}
		catch ( Exception $e )
		{
			exit( $e->getMessage() );
		}

		$this->view->assign( "keywords", $keywords );
		$this->view->assign( "sitetitle", "自動録画キーワードの管理" );
	}

	/**
	 * 自動録画キーワード削除
	 */
	public function deleteAction()
	{
		if ( $this->request->getPost('keyword_id') )
		{
			try
			{
				$rec = new DBRecord( KEYWORD_TBL, "id", $this->request->getPost('keyword_id') );

				// 一気にキャンセル
				Reservation::keyword( $rec->id, true );

				$rec->delete();
			}
			catch ( Exception $e )
			{
				exit( "Error:" . $e->getMessage() );
			}
		}
		else
			exit( "Error:キーワードIDが指定されていません" );
	}

	// 時間帯
	private function _getPrgTimes()
	{
		$prgtimes = array();
		for ( $i=0; $i < 25; $i++ )
			$prgtimes[$i] = ( $i == 24 ) ? "なし" : sprintf("%02d時～", $i);
		return $prgtimes;
	}

	// 曜日
	private function _getWeekOfDays()
	{
		return array( "なし", "月", "火", "水", "木", "金", "土", "日" );
	}
}
?>