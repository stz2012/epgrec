<?php
/**
 * epgrec - トップページコントローラ
 * @package CommonController
 * @subpackage IndexController
 */
class IndexController extends CommonController
{
	/**
	 * 前処理
	 */
	public function preAction()
	{
		// 基底クラスの処理を呼出
		parent::preAction();
		
		// 設定ファイルの有無を検査する
		if ( ! file_exists( INSTALL_PATH."/settings/config.xml") )
		{
			/*header( "Content-Type: text/html;charset=utf-8" );
			exit( "<script type=\"text/javascript\">\n" .
			"<!--\n".
			"window.open(\"install/step1.php\",\"_self\");".
			"// -->\n</script>" );*/
		}
	}
	
	/**
	 * デフォルト表示
	 */
	public function indexAction()
	{
		$DAY_OF_WEEK = array( "(日)","(月)","(火)","(水)","(木)","(金)","(土)" );

		// パラメータの処理
		// 表示する長さ（時間）
		$program_length = $this->setting->program_length;
		if ( $this->request->getQuery('length') )
		{
			$program_length = (int) $this->request->getQuery('length');
		}
		// 地上=GR/BS=BS
		$type = "GR";
		if ( $this->request->getQuery('type') )
		{
			$type = $this->request->getQuery('type');
		}
		// 現在の時間
		$top_time = mktime( date("H"), 0 , 0 );
		if ( $this->request->getQuery('time') )
		{
			if ( sscanf( $this->request->getQuery('time') , "%04d%2d%2d%2d", $y, $mon, $day, $h ) == 4 )
			{
				$tmp_time = mktime( $h, 0, 0, $mon, $day, $y );
				if ( ($tmp_time < ($top_time + 3600 * 24 * 8)) && ($tmp_time > ($top_time - 3600 * 24 * 8)) )
					$top_time = $tmp_time;
			}
		}
		$last_time = $top_time + 3600 * $program_length;

		// 時刻欄
		for( $i = 0 ; $i < $program_length; $i++ )
		{
			$tvtimes[$i] = date("H", $top_time + 3600 * $i );
		}

		// チャンネルマップ
		$tmprec = new DBRecord(CHANNEL_TBL);
		$recarr = $tmprec->fetch_array( "type", $type, "id > 0 ORDER BY sid+0 ASC" );
		foreach( $recarr as $val)
		{
			$channel_map["{$val['channel_disc']}"] = $val['channel'];
		}

		// 番組表
		$programs = array();
		$st = 0;
		$prec = null;
		try
		{
			$prec = new DBRecord(PROGRAM_TBL);
		}
		catch( Exception $e )
		{
			exit('プログラムテーブルが存在しないようです。インストールをやり直してください.');
		}
		$num_ch = 0;
		foreach( $channel_map as $channel_disc => $channel )
		{
			$prev_end = $top_time;
		 	try
		 	{
				$crec = new DBRecord( CHANNEL_TBL, "channel_disc", $channel_disc );
				$programs[$st]["skip"] = $crec->skip;
				if ( $crec->skip == 0 ) $num_ch++;
				$programs[$st]["channel_disc"] = $channel_disc;
				$programs[$st]["station_name"]  = $crec->name;
				$programs[$st]["sid"] = $crec->sid;
				$programs[$st]["ch_hash"] = md5($channel_disc);
				
				$reca = $prec->fetch_array( "channel_disc", $channel_disc,
				                                  "endtime > '".toDatetime($top_time)."' ".
				                                  "AND starttime < '". toDatetime($last_time)."' ".
				                                  "ORDER BY starttime ASC "
				                               );
				$programs[$st]['list'] = array();
				$num = 0;
				foreach( $reca as $prg )
				{
					$prg_starttime = $start = strtotime($prg['starttime']);
					$prg_endtime = strtotime($prg['endtime']);
					// 前プログラムとの空きを調べる
					if ( ($prg_starttime - $prev_end) > 0 )
					{
						$height = ($prg_starttime-$prev_end) * $this->setting->height_per_hour / 3600;
						$programs[$st]['list'][$num]['category_none'] = "none";
						$programs[$st]['list'][$num]['height'] = $height;
						$programs[$st]['list'][$num]['title'] = "";
						$programs[$st]['list'][$num]['starttime'] = "";
						$programs[$st]['list'][$num]['description'] = "";
						$num++;
					}
					$prev_end = $prg_endtime;
					
					$height = $prg_endtime - $prg_starttime;
					// $top_time より早く始まっている番組
					if ( $prg_starttime < $top_time )
					{
						$height = $prg_endtime - $top_time;
					}
					// $last_time より遅く終わる番組
					if ( $prg_endtime > $last_time )
					{
						$height = $height - ($prg_endtime - $last_time);
					}
					$height = $height * $this->setting->height_per_hour / 3600;
					
					// プログラムを埋める
					$cat = new DBRecord( CATEGORY_TBL, "id", $prg['category_id'] );
					$programs[$st]['list'][$num]['category_name'] = $cat->name_en;
					$programs[$st]['list'][$num]['height'] = $height;
					$programs[$st]['list'][$num]['title'] = $prg['title'];
					$programs[$st]['list'][$num]['starttime'] = date("H:i", $start )."" ;
					$programs[$st]['list'][$num]['description'] = $prg['description'];
					$programs[$st]['list'][$num]['prg_start'] = str_replace( "-", "/", $prg['starttime']);
					$programs[$st]['list'][$num]['duration'] = "" . (toTimestamp($prg['endtime']) - toTimestamp($prg['starttime']));
					$programs[$st]['list'][$num]['channel'] = ($prg['type'] == "GR" ? "地上D" : "BS" ) . ":". $prg['channel'] . "ch";
					$programs[$st]['list'][$num]['id'] = "" . ($prg['id']);
					$programs[$st]['list'][$num]['rec'] = DBRecord::countRecords(RESERVE_TBL, "WHERE complete = '0' AND program_id = '".$prg['id']."'" );
					$num++;
				}
			}
			catch( exception $e )
			{
				$num_ch++;	// epgの無いチャンネル対応
		 	}
		 	// 空きを埋める
			if ( ($last_time - $prev_end) > 0 )
			{
				$height = ($last_time - $prev_end) * $this->setting->height_per_hour / 3600;
				$height = $height;
				$programs[$st]['list'][$num]['category_name'] = "none";
				$programs[$st]['list'][$num]['height'] = $height;
				$programs[$st]['list'][$num]['title'] = "";
				$programs[$st]['list'][$num]['starttime'] = "";
				$programs[$st]['list'][$num]['description'] = "";
				$num++;
		 	}
			$st++;
		}
		$prec = null;
		 
		// 局の幅
		$ch_set_width = (int)($this->setting->ch_set_width);
		// 全体の幅
		$chs_width = $ch_set_width * $num_ch;

		// GETパラメタ
		$get_param = $this->getCurrentUri() . "?type=".$type."&length=".$program_length."";

		// カテゴリ一覧
		$crec = DBRecord::createRecords( CATEGORY_TBL );
		$cats = array();
		$num = 0;
		foreach( $crec as $val )
		{
			$cats[$num]['name_en'] = $val->name_en;
			$cats[$num]['name_jp'] = $val->name_jp;
			$num++;
		}
		$this->view->assign( "cats", $cats );

		// タイプ選択
		$types = array();
		$i = 0;
		if ( $this->setting->bs_tuners != 0 )
		{
			$types[$i]['selected'] = $type == "BS" ? 'class="selected"' : "";
			$types[$i]['link'] = $this->getCurrentUri() . "?type=BS&length=".$program_length."&time=".date( "YmdH", $top_time);
			$types[$i]['name'] = "BS";
			$i++;

			// CS
			if ($this->setting->cs_rec_flg != 0)
			{
				$types[$i]['selected'] = $type == "CS" ? 'class="selected"' : "";
				$types[$i]['link'] = $this->getCurrentUri() . "?type=CS&length=".$program_length."&time=".date( "YmdH", $top_time);
				$types[$i]['name'] = "CS";
				$i++;
			}
		}
		if ( $this->setting->gr_tuners != 0 )
		{
			$types[$i]['selected'] = $type == "GR" ? 'class="selected"' : "";
			$types[$i]['link'] = $this->getCurrentUri() . "?type=GR&length=".$program_length."&time=".date( "YmdH", $top_time);
			$types[$i]['name'] = "地上デジタル";
			$i++;
		}
		$this->view->assign( "types", $types );

		// 日付選択
		$days = array();
		$day = array();
		$day['d'] = "昨日";
		$day['link'] = $get_param . "&time=". date( "YmdH", time() - 3600 *24 );
		$day['ofweek'] = "";
		$day['selected'] = $top_time < mktime( 0, 0 , 0) ? 'class="selected"' : '';

		array_push( $days , $day );
		$day['d'] = "現在";
		$day['link'] = $get_param;
		$day['ofweek'] = "";
		$day['selected'] = "";
		array_push( $days, $day );
		for( $i = 0 ; $i < 8 ; $i++ )
		{
			$day['d'] = "".date("d", time() + 24 * 3600 * $i ) . "日";
			$day['link'] = $get_param . "&time=".date( "Ymd", time() + 24 * 3600 * $i) . date("H" , $top_time );
			$day['ofweek'] = $DAY_OF_WEEK[(int)date( "w", time() + 24 * 3600 * $i )];
			$day['selected'] = date("d", $top_time) == date("d", time() + 24 * 3600 * $i ) ? 'class="selected"' : '';
			array_push( $days, $day );
		}
		$this->view->assign( "days" , $days );

		// 時間選択
		$toptimes = array();
		for( $i = 0 ; $i < 24; $i+=4 )
		{
			$tmp = array();
			$tmp['hour'] = sprintf( "%02d:00", $i );
			$tmp['link'] = $get_param . "&time=".date("Ymd", $top_time ) . sprintf("%02d", $i );
			array_push( $toptimes, $tmp );
		}
		$this->view->assign( "toptimes" , $toptimes );

		$this->view->assign( "tvtimes", $tvtimes );
		$this->view->assign( "programs", $programs );
		$this->view->assign( "ch_set_width", (int)($this->setting->ch_set_width) );
		$this->view->assign( "chs_width", $chs_width );
		$this->view->assign( "height_per_hour", $this->setting->height_per_hour );
		$this->view->assign( "height_per_min", $this->setting->height_per_hour / 60 );
		$this->view->assign( "num_ch", $num_ch );
		$this->view->assign( "num_all_ch" , count( $channel_map ) );

		$sat_type = array('GR' => '地上デジタル', 'BS' => 'BSデジタル', 'CS' => 'CSデジタル');
		$sitetitle = date( "Y", $top_time ) . "年" . date( "m", $top_time ) . "月" . date( "d", $top_time ) . "日". date( "H", $top_time ) .
		              "時～".$sat_type[$type]."番組表";
		$this->view->assign("sitetitle", $sitetitle );
		$this->view->assign("top_time", str_replace( "-", "/" ,toDatetime($top_time)) );
		$this->view->assign("last_time", str_replace( "-", "/" ,toDatetime($last_time)) );
	}

	/**
	 * チャンネル情報更新
	 */
	public function setChannelInfoAction()
	{
		if ( $this->request->getPost('sid') && $this->request->getPost('channel_disc') && $this->request->getPost('skip') )
		{
			try
			{
				$crec = new DBRecord( CHANNEL_TBL, "channel_disc", $this->request->getPost('channel_disc') );
				$crec->sid = trim($this->request->getPost('sid'));
				$crec->skip = (int)(trim($this->request->getPost('skip')));
			}
			catch( Exception $e ) {
				exit("Error: チャンネル情報更新失敗" );
			}
		}
	}

	/**
	 * 録画フォーム
	 */
	public function reserveFormAction()
	{
		global $RECORD_MODE;
		if ( ! $this->request->getQuery('program_id') ) exit("Error: 番組IDが指定されていません" );
		$program_id = $this->request->getQuery('program_id');
		$record_modes = $RECORD_MODE;
		$record_modes[(int)($this->setting->autorec_mode)]['selected'] = "selected";

		try
		{
			$prec = new DBRecord( PROGRAM_TBL, "id", $program_id );

			sscanf( $prec->starttime, "%4d-%2d-%2d %2d:%2d:%2d", $syear, $smonth, $sday, $shour, $smin, $ssec );
			sscanf( $prec->endtime, "%4d-%2d-%2d %2d:%2d:%2d", $eyear, $emonth, $eday, $ehour, $emin, $esec );

			$crecs = DBRecord::createRecords( CATEGORY_TBL );
			$cats = array();
			foreach( $crecs as $crec )
			{
				$cat = array();
				$cat['id'] = $crec->id;
				$cat['name'] = $crec->name_jp;
				$cat['selected'] = $prec->category_id == $cat['id'] ? "selected" : "";
				
				array_push( $cats , $cat );
			}

			$this->view->assign( "syear", $syear );
			$this->view->assign( "smonth", $smonth );
			$this->view->assign( "sday", $sday );
			$this->view->assign( "shour", $shour );
			$this->view->assign( "smin" ,$smin );
			$this->view->assign( "eyear", $eyear );
			$this->view->assign( "emonth", $emonth );
			$this->view->assign( "eday", $eday );
			$this->view->assign( "ehour", $ehour );
			$this->view->assign( "emin" ,$emin );

			$this->view->assign( "type", $prec->type );
			$this->view->assign( "channel", $prec->channel );
			$this->view->assign( "channel_id", $prec->channel_id );
			$this->view->assign( "record_mode" , $record_modes );

			$this->view->assign( "title", $prec->title );
			$this->view->assign( "description", $prec->description );

			$this->view->assign( "cats" , $cats );

			$this->view->assign( "program_id", $prec->id );
		}
		catch( exception $e ) {
			exit( "Error:". $e->getMessage() );
		}
	}

	/**
	 * 簡易録画
	 */
	public function simpleAction()
	{
		if ( ! $this->request->getQuery('program_id') ) exit("Error: 番組が指定されていません" );
		$program_id = $this->request->getQuery('program_id');

		try
		{
			Reservation::simple( $program_id , 0, $this->setting->autorec_mode);
		}
		catch( Exception $e ) {
			exit( "Error:". $e->getMessage() );
		}
	}

	/**
	 * 詳細録画
	 */
	public function customAction()
	{
		$program_id = 0;
		if ( $this->request->getPost('program_id') ) $program_id = $this->request->getPost('program_id');

		if (!(
		   $this->request->getPost('shour')       && 
		   $this->request->getPost('smin')        &&
		   $this->request->getPost('smonth')      &&
		   $this->request->getPost('sday')        &&
		   $this->request->getPost('syear')       &&
		   $this->request->getPost('ehour')       &&
		   $this->request->getPost('emin')        &&
		   $this->request->getPost('emonth')      &&
		   $this->request->getPost('eday')        &&
		   $this->request->getPost('eyear')       &&
		   $this->request->getPost('channel_id')  &&
		   $this->request->getPost('title')       &&
		   $this->request->getPost('description') &&
		   $this->request->getPost('category_id') &&
		   $this->request->getPost('record_mode'))
		) {
			exit("Error:予約に必要な値がセットされていません");
		}

		$start_time = @mktime( $this->request->getPost('shour'), $this->request->getPost('smin'), 0, $this->request->getPost('smonth'), $this->request->getPost('sday'), $this->request->getPost('syear') );
		if ( ($start_time < 0) || ($start_time === false) )
		{
			exit("Error:開始時間が不正です" );
		}

		$end_time = @mktime( $this->request->getPost('ehour'), $this->request->getPost('emin'), 0, $this->request->getPost('emonth'), $this->request->getPost('eday'), $this->request->getPost('eyear') );
		if ( ($end_time < 0) || ($end_time === false) )
		{
			exit("Error:終了時間が不正です" );
		}

		$channel_id = $this->request->getPost('channel_id');
		$title = $this->request->getPost('title');
		$description = $this->request->getPost('description');
		$category_id = $this->request->getPost('category_id');
		$mode = $this->request->getPost('record_mode');

		$rval = 0;
		try
		{
			$rval = Reservation::custom(
				toDatetime($start_time),
				toDatetime($end_time),
				$channel_id,
				$title,
				$description,
				$category_id,
				$program_id,
				0,		// 自動録画
				$mode,	// 録画モード
				1		// ダーティフラグ
			);
		}
		catch( Exception $e )
		{
			exit( "Error:".$e->getMessage() );
		}
	}

	/**
	 * 予約キャンセル
	 */
	public function cancelAction()
	{
		$program_id = 0;
		$reserve_id = 0;
		$rec = null;
		$path = "";

		if ( $this->request->getQuery('program_id') )
		{
			$program_id = $this->request->getQuery('program_id');
		}
		else if ( $this->request->getQuery('reserve_id') )
		{
			$reserve_id = $this->request->getQuery('reserve_id');
			try
			{
				$rec = new DBRecord( RESERVE_TBL, "id" , $reserve_id );
				$program_id = $rec->program_id;
				
				if ( $this->request->getQuery('delete_file') )
				{
					if ( $this->request->getQuery('delete_file') == 1 )
					{
						$path = INSTALL_PATH."/".$this->setting->spool."/".$rec->path;
					}
				}
			}
			catch( Exception $e )
			{
				// 無視
			}
		}

		// 手動取り消しのときには、その番組を自動録画対象から外す
		if ( $program_id )
		{
			try
			{
				$rec = new DBRecord(PROGRAM_TBL, "id", $program_id );
				$rec->autorec = 0;
			}
			catch( Exception $e )
			{
				// 無視
			}
		}

		// 予約取り消し実行
		try
		{
			Reservation::cancel( $reserve_id, $program_id );
			if ( $this->request->getQuery('delete_file') )
			{
				if ( $this->request->getQuery('delete_file') == 1 )
				{
					// ファイルを削除
					if ( file_exists( $path) )
					{
						@unlink($path);
						@unlink($path.".jpg");
					}
				}
			}
		}
		catch( Exception $e )
		{
			exit( "Error" . $e->getMessage() );
		}
	}

	/**
	 * 予約変更
	 */
	public function changeAction()
	{
		if ( ! $this->request->getPost('reserve_id') )
		{
			exit("Error: IDが指定されていません" );
		}
		$reserve_id = $this->request->getPost('reserve_id');

		$dbh = false;
		if ( $this->setting->mediatomb_update == 1 )
		{
			$sqlstr = "use ".$this->setting->db_name;
			@mysql_query( $sqlstr );
			$sqlstr = "set NAME utf8";
			@mysql_query( $sqlstr );
		}

		try
		{
			$rec = new DBRecord(RESERVE_TBL, "id", $reserve_id );
			
			if ( $this->request->getPost('title') )
			{
				$rec->title = trim( $this->request->getPost('title') );
				$rec->dirty = 1;
				if ( ($dbh !== false) && ($rec->complete == 1) )
				{
					$title = trim( $this->request->getPost('title'));
					$title .= "(".date("Y/m/d", toTimestamp($rec->starttime)).")";
					$sqlstr = "update mt_cds_object set dc_title='".$title."' where metadata regexp 'epgrec:id=".$reserve_id."$'";
					@mysql_query( $sqlstr );
				}
			}
			
			if ( $this->request->getPost('description') )
			{
				$rec->description = trim( $this->request->getPost('description') );
				$rec->dirty = 1;
				if ( ($dbh !== false) && ($rec->complete == 1) )
				{
					$desc = "dc:description=".trim( $this->request->getPost('description'));
					$desc .= "&epgrec:id=".$reserve_id;
					$sqlstr = "update mt_cds_object set metadata='".$desc."' where metadata regexp 'epgrec:id=".$reserve_id."$'";
					@mysql_query( $sqlstr );
				}
			}
		}
		catch( Exception $e )
		{
			exit("Error: ". $e->getMessage());
		}
	}
}
?>