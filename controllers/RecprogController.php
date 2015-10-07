<?php
/**
 * epgrec - 録画番組コントローラ
 * @package CommonController
 * @subpackage RecprogController
 */
class RecprogController extends CommonController
{
	/**
	 * 録画予約一覧表示
	 */
	public function indexAction()
	{
		global $RECORD_MODE;
		$search = $this->request->getPost('search');
		$category_id = ($this->request->getPost('category_id')) ? $this->request->getPost('category_id') : 0;
		$channel_id = ($this->request->getPost('station')) ? $this->request->getPost('station') : 0;

		$reservations = array();
		$rvs = $this->model->getReserveData($this->request->getPost());
		foreach ( $rvs as $r )
		{
			$r['mode'] = $RECORD_MODE[$r['mode']]['name'];
			array_push( $reservations, $r );
		}

		$this->view->assign( 'sitetitle',    '録画予約一覧' );
		$this->view->assign( 'reservations', $reservations );
		$this->view->assign( 'search',       $search );
		$this->view->assign( 'stations',     $this->model->getStationOptions() );
		$this->view->assign( 'sel_station',  $channel_id );
		$this->view->assign( 'categorys',    $this->model->getCategoryOptions() );
		$this->view->assign( 'sel_category', $category_id );
	}

	/**
	 * 録画済一覧表示
	 */
	public function recordedAction()
	{
		global $RECORD_MODE;
		$search = $this->request->getPost('search');
		$category_id = ($this->request->getPost('category_id')) ? $this->request->getPost('category_id') : 0;
		$channel_id = ($this->request->getPost('station')) ? $this->request->getPost('station') : 0;

		$records = array();
		$rvs = $this->model->getRecordedData($this->request->getPost());
		foreach ( $rvs as $r )
		{
			$param = array();
			$param['reserve_id'] = $r['id'];
			$r['asf']         = $this->getCurrentUri(false).'/viewer?'.UtilString::buildQueryString($param);
			$r['title']       = htmlspecialchars($r['title'], ENT_QUOTES);
			$r['description'] = htmlspecialchars($r['description'], ENT_QUOTES);
			$r['thumb']       = '<img src="'.$this->setting->install_url.$this->setting->thumbs.'/'.htmlentities($r['path'], ENT_QUOTES, 'UTF-8').'.jpg" />';
			$r['mode']        = $RECORD_MODE[$r['mode']]['name'];
			// 録画終了時間を１０分過ぎているのに、完了フラグが立ってない場合
			if ( time() > (toTimestamp($r['endtime']) + 600) && $r['complete'] == 0 )
			{
				// 終わったことにする
				$this->model->updateRow($this->model->getFullTblName(RESERVE_TBL), array('complete' => 1), array('id' => $r['id']));
			}
			if (file_exists(INSTALL_PATH.$this->setting->spool.'/'.$r['path']))
			{
				// 録画ファイルのサイズを計算
				$r['fsize'] = filesize_n(INSTALL_PATH.$this->setting->spool.'/'.$r['path']);
			}
			else
			{
				// 録画ファイルが存在しない予約は消去
				$this->model->deleteRow($this->model->getFullTblName(RESERVE_TBL), array('id' => $r['id']));
				continue;
			}
			array_push( $records, $r );
		}

		$this->view->assign( 'sitetitle',    '録画済一覧' );
		$this->view->assign( 'records',      $records );
		$this->view->assign( 'search',       $search );
		$this->view->assign( 'stations',     $this->model->getStationOptions() );
		$this->view->assign( 'sel_station',  $channel_id );
		$this->view->assign( 'categorys',    $this->model->getCategoryOptions() );
		$this->view->assign( 'sel_category', $category_id );
		$this->view->assign( 'use_thumbs',   $this->setting->use_thumbs );
	}

	/**
	 * ビューアー表示
	 */
	public function viewerAction()
	{
		header('Expires: Thu, 01 Dec 1994 16:00:00 GMT');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s'). ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');

		if ( ! $this->request->getQuery('reserve_id') )
			jdialog('予約番号が指定されていません', $this->getCurrentUri(false).'/recorded');
		$reserve_id = $this->request->getQuery('reserve_id');

		try
		{
			$rrec = new DBRecord( RESERVE_TBL, 'id', $reserve_id );

			$start_time = toTimestamp($rrec->starttime);
			$end_time = toTimestamp($rrec->endtime );
			$duration = $end_time - $start_time + $this->setting->former_time;

			$dh = $duration / 3600;
			$duration = $duration % 3600;
			$dm = $duration / 60;
			$duration = $duration % 60;
			$ds = $duration;
			
			$title    = htmlspecialchars(str_replace(array("\r\n", "\r", "\n"), '', $rrec->title),ENT_QUOTES);
			$abstract = htmlspecialchars(str_replace(array("\r\n", "\r", "\n"), '', $rrec->description),ENT_QUOTES);
			
			header('Content-type: video/x-ms-asf; charset="UTF-8"');
			header('Content-Disposition: inline; filename="'.$rrec->path.'.asx"');
			echo '<ASX version = "3.0">';
			echo '<PARAM NAME = "Encoding" VALUE = "UTF-8" />';
			echo '<ENTRY>';
			if ( ! $rrec->complete )
			{
				$param = array();
				$param['reserve_id'] = $rrec->id;
				echo '<REF HREF="'.$this->getCurrentUri(false).'/sendStream?'.UtilString::buildQueryString($param).'" />';
			}
			echo '<REF HREF="'.$this->setting->install_url.$this->setting->spool.'/'.$rrec->path .'" />';
			echo '<TITLE>'.$title.'</TITLE>';
			echo '<ABSTRACT>'.$abstract.'</ABSTRACT>';
			echo '<DURATION VALUE="'.sprintf( '%02d:%02d:%02d', $dh, $dm, $ds ).'" />';
			echo '</ENTRY>';
			echo '</ASX>';
		}
		catch (exception $e )
		{
			exit( $e->getMessage() );
		}
		exit;
	}

	/**
	 * ストリーミング配信
	 */
	public function sendStreamAction()
	{
		header('Expires: Thu, 01 Dec 1994 16:00:00 GMT');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s'). ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');

		if ( ! $this->request->getQuery('reserve_id') )
			jdialog('予約番号が指定されていません', $this->getCurrentUri(false).'/recorded');
		$reserve_id = $this->request->getQuery('reserve_id');

		try
		{
			$rrec = new DBRecord( RESERVE_TBL, 'id', $reserve_id );

			$start_time = toTimestamp($rrec->starttime);
			$end_time = toTimestamp($rrec->endtime );
			$duration = $end_time - $start_time;
			$size = 3 * 1024 * 1024 * $duration;	// 1秒あたり3MBと仮定

			header('Content-type: video/mpeg');
			header('Content-Disposition: inline; filename="'.$rrec->path.'"');
			header('Content-Length: ' . $size );
			ob_clean();
			flush();

			$fp = @fopen( INSTALL_PATH.$this->setting->spool.'/'.$rrec->path, 'r' );
			if ( $fp !== false )
			{
				do
				{
					$start = microtime(true);
					if ( feof( $fp ) ) break;
					echo fread( $fp, 6292 );
					@usleep( 2000 - (int)((microtime(true) - $start) * 1000 * 1000));
				}
				while ( connection_aborted() == 0 );
			}
			fclose($fp);
		}
		catch (exception $e )
		{
			exit( $e->getMessage() );
		}
		exit;
	}
}
?>