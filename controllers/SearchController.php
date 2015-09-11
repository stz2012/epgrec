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
		$options = " WHERE starttime > '".date("Y-m-d H:i:s", time() + 300 )."'";

		// 曜日
		$weekofdays = array(
							array( "name" => "月", "id" => 0, "selected" => "" ),
							array( "name" => "火", "id" => 1, "selected" => "" ),
							array( "name" => "水", "id" => 2, "selected" => "" ),
							array( "name" => "木", "id" => 3, "selected" => "" ),
							array( "name" => "金", "id" => 4, "selected" => "" ),
							array( "name" => "土", "id" => 5, "selected" => "" ),
							array( "name" => "日", "id" => 6, "selected" => "" ),
							array( "name" => "なし", "id" => 7, "selected" => "" ),
		);


		$autorec_modes = $RECORD_MODE;
		$autorec_modes[(int)($this->setting->autorec_mode)]['selected'] = "selected";

		$search = "";
		$use_regexp = 0;
		$type = "*";
		$category_id = 0;
		$channel_id = 0;
		$weekofday = 7;
		$prgtime = 24;

		// パラメータの処理
		if ( $this->request->getPost('do_search') ) {
			if ( $this->request->getPost('search') ){
				$search = $this->request->getPost('search');
				if ( $this->request->getPost('use_regexp') && $this->request->getPost('use_regexp') ) {
					$use_regexp = (int)($this->request->getPost('use_regexp'));
				}
			}
			if ( $this->request->getPost('type') ){
				$type = $this->request->getPost('type');
			}
			if ( $this->request->getPost('category_id') ) {
				$category_id = (int)($this->request->getPost('category_id'));
			}
			if ( $this->request->getPost('station') ) {
				$channel_id = (int)($this->request->getPost('station'));
			}
			if ( $this->request->getPost('weekofday') ) {
				$weekofday = (int)($this->request->getPost('weekofday'));
			}
			if ( $this->request->getPost('prgtime') ) {
				$prgtime = (int)($this->request->getPost('prgtime'));
			}
		}

		$do_keyword = 0;
		if ( ($search != "") || ($type != "*") || ($category_id != 0) || ($channel_id != 0) )
			$do_keyword = 1;
			
		try{
			$precs = Keyword::search( $search, $use_regexp, $type, $category_id, $channel_id, $weekofday, $prgtime );
			
			$programs = array();
			foreach( $precs as $p ) {
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
			
			$k_category_name = "";
			$crecs = DBRecord::createRecords(CATEGORY_TBL);
			$cats = array();
			$cats[0]['id'] = 0;
			$cats[0]['name'] = "すべて";
			$cats[0]['selected'] = $category_id == 0 ? "selected" : "";
			foreach( $crecs as $c ) {
				$arr = array();
				$arr['id'] = $c->id;
				$arr['name'] = $c->name_jp;
				$arr['selected'] = $c->id == $category_id ? "selected" : "";
				if ( $c->id == $category_id ) $k_category_name = $c->name_jp;
				array_push( $cats, $arr );
			}
			
			$types = array();
			$types[0]['name'] = "すべて";
			$types[0]['value'] = "*";
			$types[0]['selected'] = $type == "*" ? "selected" : "";
			if ( $this->setting->gr_tuners != 0 ) {
				$arr = array();
				$arr['name'] = "GR";
				$arr['value'] = "GR";
				$arr['selected'] = $type == "GR" ? "selected" : "";
				array_push( $types, $arr );
			}
			if ( $this->setting->bs_tuners != 0 ) {
				$arr = array();
				$arr['name'] = "BS";
				$arr['value'] = "BS";
				$arr['selected'] = $type == "BS" ? "selected" : "";
				array_push( $types, $arr );

				// CS
				if ($this->setting->cs_rec_flg != 0) {
					$arr = array();
					$arr['name'] = "CS";
					$arr['value'] = "CS";
					$arr['selected'] = $type == "CS" ? "selected" : "";
					array_push( $types, $arr );
				}
			}
			
			$k_station_name = "";
			$crecs = DBRecord::createRecords(CHANNEL_TBL);
			$stations = array();
			$stations[0]['id'] = 0;
			$stations[0]['name'] = "すべて";
			$stations[0]['selected'] = (! $channel_id) ? "selected" : "";
			foreach( $crecs as $c ) {
				$arr = array();
				$arr['id'] = $c->id;
				$arr['name'] = $c->name;
				$arr['selected'] = $channel_id == $c->id ? "selected" : "";
				if ( $channel_id == $c->id ) $k_station_name = $c->name;
				array_push( $stations, $arr );
			}
			$weekofdays["$weekofday"]["selected"] = "selected" ;
			
			// 時間帯
			$prgtimes = array();
			for( $i=0; $i < 25; $i++ ) {
				array_push( $prgtimes, 
					array(  "name" => ( $i == 24  ? "なし" : sprintf("%0d時～",$i) ),
							"value" => $i,
							"selected" =>  ( $i == $prgtime ? "selected" : "" ) )
				);
			}

			$smarty = new Smarty();
			$this->view->assign("sitetitle","番組検索");
			$this->view->assign("do_keyword", $do_keyword );
			$this->view->assign( "programs", $programs );
			$this->view->assign( "cats", $cats );
			$this->view->assign( "k_category", $category_id );
			$this->view->assign( "k_category_name", $k_category_name );
			$this->view->assign( "types", $types );
			$this->view->assign( "k_type", $type );
			$this->view->assign( "search" , $search );
			$this->view->assign( "use_regexp", $use_regexp );
			$this->view->assign( "stations", $stations );
			$this->view->assign( "k_station", $channel_id );
			$this->view->assign( "k_station_name", $k_station_name );
			$this->view->assign( "weekofday", $weekofday );
			$this->view->assign( "k_weekofday", $weekofdays["$weekofday"]["name"] );
			$this->view->assign( "weekofday", $weekofday );
			$this->view->assign( "weekofdays", $weekofdays );
			$this->view->assign( "autorec_modes", $autorec_modes );
			$this->view->assign( "prgtimes", $prgtimes );
			$this->view->assign( "prgtime", $prgtime );
		}
		catch( exception $e ) {
			exit( $e->getMessage() );
		}
	}

	/**
	 * 自動録画キーワードの管理
	 */
	public function keywordAction()
	{
		global $RECORD_MODE;
		$weekofdays = array( "月", "火", "水", "木", "金", "土", "日", "なし" );
		$prgtimes = array();
		for( $i=0 ; $i < 25; $i++ ) {
			$prgtimes[$i] = $i == 24 ? "なし" : $i."時～";
		}

		// 新規キーワードがポストされた
		if ( $this->request->getPost('add_keyword') ) {
			if ( $this->request->getPost('add_keyword') == 1 ) {
				try {
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
				catch( Exception $e ) {
					exit( $e->getMessage() );
				}
			}
		}

		$keywords = array();
		try {
			$recs = Keyword::createRecords(KEYWORD_TBL);
			foreach( $recs as $rec ) {
				$arr = array();
				$arr['id'] = $rec->id;
				$arr['keyword'] = $rec->keyword;
				$arr['type'] = $rec->type == "*" ? "すべて" : $rec->type;
				
				if ( $rec->channel_id ) {
					$crec = new DBRecord(CHANNEL_TBL, "id", $rec->channel_id );
					$arr['channel'] = $crec->name;
				}
				else $arr['channel'] = 'すべて';
				
				if ( $rec->category_id ) {
					$crec = new DBRecord(CATEGORY_TBL, "id", $rec->category_id );
					$arr['category'] = $crec->name_jp;
				}
				else $arr['category'] = 'すべて';
				
				$arr['use_regexp'] = $rec->use_regexp;
				
				$arr['weekofday'] = $weekofdays["$rec->weekofday"];
				
				$arr['prgtime'] = $prgtimes["$rec->prgtime"];
				
				$arr['autorec_mode'] = $RECORD_MODE[(int)$rec->autorec_mode]['name'];
				
				array_push( $keywords, $arr );
			}
		}
		catch( Exception $e ) {
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
		if ( $this->request->getPost('keyword_id') ) {
			try {
				$rec = new Keyword( "id", $this->request->getPost('keyword_id') );
				$rec->delete();
			}
			catch( Exception $e ) {
				exit( "Error:" . $e->getMessage() );
			}
		}
		else
			exit( "Error:キーワードIDが指定されていません" );
	}
}
?>