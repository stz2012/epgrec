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
		global $RECORD_MODE;
		$autorec_modes = $RECORD_MODE;
		$autorec_modes[(int)($this->setting->autorec_mode)]['selected'] = 'selected="selected"';

		$search = "";
		$use_regexp = 0;
		$type = "*";
		$category_id = 0;
		$channel_id = 0;
		$weekofday = 7;
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
			if ( $this->request->getPost('weekofday') )
				$weekofday = (int)($this->request->getPost('weekofday'));
			if ( $this->request->getPost('prgtime') )
				$prgtime = (int)($this->request->getPost('prgtime'));
		}

		$do_keyword = 0;
		if ( ($search != "") || ($type != "*") || ($category_id != 0) || ($channel_id != 0) )
			$do_keyword = 1;

		try
		{
			$precs = Keyword::search( $search, $use_regexp, $type, $category_id, $channel_id, $weekofday, $prgtime );

			$programs = array();
			foreach ( $precs as $p )
			{
				$ch  = new DBRecord(CHANNEL_TBL, "id", $p->channel_id );
				$cat = new DBRecord(CATEGORY_TBL, "id", $p->category_id );
				$arr = array();
				$arr['type'] = $p->type;
				$arr['station_name'] = $ch->name;
				$arr['starttime'] = $p->starttime;
				$arr['endtime'] = $p->endtime;
				$arr['title'] = $p->title;
				$arr['description'] = $p->description;
				$arr['id'] = $p->id;
				$arr['cat'] = $cat->name_en;
				$arr['rec'] = DBRecord::countRecords(RESERVE_TBL, "WHERE program_id='".$p->id."'");
				
				array_push( $programs, $arr );
			}
		}
		catch( exception $e )
		{
			exit( $e->getMessage() );
		}

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
		$this->view->assign( "autorec_modes", $autorec_modes );
	}

	/**
	 * 自動録画キーワードの管理
	 */
	public function keywordAction()
	{
		global $RECORD_MODE;
		$prgtimes = $this->_getPrgTimes();
		$weekofdays = $this->_getWeekOfDays();

		// 新規キーワードがポストされた
		if ( $this->request->getPost('add_keyword') == 1 )
		{
			try
			{
				$rec = new Keyword();
				$rec->keyword = $this->request->getPost('k_search');
				$rec->type = $this->request->getPost('k_type');
				$rec->category_id = $this->request->getPost('k_category');
				$rec->channel_id = $this->request->getPost('k_station');
				$rec->use_regexp = $this->request->getPost('k_use_regexp');
				$rec->weekofday = $this->request->getPost('k_weekofday');
				$rec->prgtime   = $this->request->getPost('k_prgtime');
				$rec->autorec_mode = $this->request->getPost('autorec_mode');
				
				// 録画予約実行
				$rec->reservation();
			}
			catch( Exception $e )
			{
				exit( $e->getMessage() );
			}
		}

		$keywords = array();
		try
		{
			$recs = Keyword::createRecords(KEYWORD_TBL);
			foreach ( $recs as $rec )
			{
				$arr = array();
				$arr['id'] = $rec->id;
				$arr['keyword'] = $rec->keyword;
				$arr['use_regexp'] = $rec->use_regexp;
				$arr['type'] = $rec->type == "*" ? "すべて" : $rec->type;
				if ( $rec->channel_id )
				{
					$crec = new DBRecord(CHANNEL_TBL, "id", $rec->channel_id );
					$arr['channel'] = $crec->name;
				}
				else
					$arr['channel'] = 'すべて';
				if ( $rec->category_id )
				{
					$crec = new DBRecord(CATEGORY_TBL, "id", $rec->category_id );
					$arr['category'] = $crec->name_jp;
				}
				else
					$arr['category'] = 'すべて';
				$arr['prgtime'] = $prgtimes["$rec->prgtime"];
				$arr['weekofday'] = $weekofdays["$rec->weekofday"];
				$arr['autorec_mode'] = $RECORD_MODE[(int)$rec->autorec_mode]['name'];
				
				array_push( $keywords, $arr );
			}
		}
		catch( Exception $e )
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
				$rec = new Keyword( "id", $this->request->getPost('keyword_id') );
				$rec->delete();
			}
			catch( Exception $e )
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
		return array( "月", "火", "水", "木", "金", "土", "日", "なし" );
	}
}
?>