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
			$r['title']       = UtilString::getSanitizeData($r['title']);
			$r['description'] = UtilString::getSanitizeData($r['description']);
			$r['thumb_src']   = BASE_URI."thumbs/{$r['id']}.jpg";
			$r['thumb_alt']   = UtilString::getSanitizeData($r['title']);
			$r['mode']        = $RECORD_MODE[$r['mode']]['name'];
			// 録画終了時間を１０分過ぎているのに、完了フラグが立ってない場合
			if ( time() > (toTimestamp($r['endtime']) + 600) && $r['complete'] == 0 )
			{
				// 終わったことにする
				$this->model->setRecordFinished($r['id']);
			}
			if (file_exists(INSTALL_PATH.$this->setting->spool.'/'.$r['path']))
			{
				// 録画ファイルのサイズを計算
				$r['fsize'] = $this->_filesize(INSTALL_PATH.$this->setting->spool.'/'.$r['path']);
			}
			else
			{
				// 録画ファイルが存在しない予約は消去
				$this->model->delReserveData($r['id']);
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
			$duration = $end_time - $start_time + (int)$this->setting->former_time;

			$dh = $duration / 3600;
			$duration = $duration % 3600;
			$dm = $duration / 60;
			$duration = $duration % 60;
			$ds = $duration;
			
			$title    = UtilString::getSanitizeData(str_replace(array("\r\n", "\r", "\n"), '', $rrec->title));
			$abstract = UtilString::getSanitizeData(str_replace(array("\r\n", "\r", "\n"), '', $rrec->description));
			
			header('Content-type: video/x-ms-asf; charset="UTF-8"');
			header('Content-Disposition: inline; filename="'.$rrec->path.'.asx"');
			echo '<ASX version = "3.0">';
			echo '<PARAM NAME = "Encoding" VALUE = "UTF-8" />';
			echo '<ENTRY>';
			$param = array();
			$param['SESS_ID'] = session_id();
			$param['reserve_id'] = $rrec->id;
			echo '<REF HREF="'.$this->_getCurrentHost().$this->getCurrentUri(false).'/sendStream?'.UtilString::buildQueryString($param).'" />';
			echo '<TITLE>'.$title.'</TITLE>';
			echo '<ABSTRACT>'.$abstract.'</ABSTRACT>';
			echo '<DURATION VALUE="'.sprintf( '%02d:%02d:%02d', $dh, $dm, $ds ).'" />';
			echo '</ENTRY>';
			echo '</ASX>';
		}
		catch ( Exception $e )
		{
			UtilLog::writeLog($e->getMessage());
		}
		exit;
	}

	/**
	 * ストリーミング配信
	 */
	public function sendStreamAction()
	{
		if ( ! $this->request->getQuery('reserve_id') )
			jdialog('予約番号が指定されていません', $this->getCurrentUri(false).'/recorded');
		$reserve_id = $this->request->getQuery('reserve_id');

		try
		{
			$rrec = new DBRecord( RESERVE_TBL, 'id', $reserve_id );
			$stream = new VideoStream(INSTALL_PATH.$this->setting->spool.'/'.$rrec->path);
			$stream->run();
		}
		catch ( Exception $e )
		{
			UtilLog::writeLog($e->getMessage());
		}
		exit;
	}

	/**
	 * ディスク情報取得
	 */
	public function getDiskInfoAction()
	{
		$disk = INSTALL_PATH . $this->setting->spool;
		$param = array();
		$param['disk_total'] = disk_total_space($disk);
		$param['disk_free']  = disk_free_space($disk);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($param);
		exit;
	}

	private function _getCurrentHost()
	{
		global $_SERVER;
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
			$protocol = 'https://';
		else
			$protocol = 'http://';
		$host = $_SERVER['HTTP_HOST'];
		if (isset($_SERVER['HTTP_PORT']) &&
			(($protocol == 'http://' && $_SERVER['HTTP_PORT'] != '80') ||
			($protocol == 'https://' && $_SERVER['HTTP_PORT'] != '443')))
		{
			$port = ':' . $_SERVER['HTTP_PORT'];
		}
		else
		{
			$port = '';
		}
		return $protocol . $host . $port;
	}

	private function _filesize($path)
	{
		$size = @filesize($path);
		if ( $size <= 0 )
		{
			ob_start();
			system('ls -al "'.$path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
			$size = ob_get_clean();
		}
		return $this->_human_filesize($size);
	}

	private function _human_filesize($bytes, $decimals = 2)
	{
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
}
?>