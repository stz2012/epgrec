<?php
/**
 * Epgrecメッセージクラス
 */
class EpgrecMsg
{
	/**
	 * @var int 予約ID
	 */
	protected $reserve_id;

	/**
	 * @var resource 受信用メッセージハンドラ
	 */
	protected $msgh_r = null;

	/**
	 * @var resource 送信用メッセージハンドラ
	 */
	protected $msgh_w = null;

	/**
	 * コンストラクタ
	 * @param int $reserve_id 予約ID
	 */
	function __construct( $reserve_id )
	{
		$this->reserve_id = $reserve_id;

		// メッセージハンドラを得る
		$ipc_key = ftok( RECORDER_CMD, 'R' );
		$this->msgh_r = msg_get_queue( $ipc_key );
		$ipc_key = ftok( RECORDER_CMD, 'W' );
		$this->msgh_w = msg_get_queue( $ipc_key );
	}

	/**
	 * メッセージ受信（ノンブロッキング）
	 * @return mixed メッセージ
	 */
	public function recvMessage()
	{
		$r = msg_receive($this->msgh_r, (int)$this->reserve_id , $msgtype, 1024, $message, TRUE, MSG_IPC_NOWAIT | MSG_NOERROR);
		if ( $r ) return $message;
		return null;
	}

	/**
	 * メッセージ送信
	 * @param string $msg メッセージ
	 */
	public function sendMessage( $msg )
	{
		msg_send( $this->msgh_w, (int)$this->reserve_id, $msg );
		sleep(1);	// 相手が受信してくれそうな時間だけ待つ
	}

	/**
	 * プロセス終了（子プロセスも含む）
	 * @param resource $p 親プロセスハンドル
	 * @return bool
	 */
	public function termProcess( $p )
	{
		$status = proc_get_status( $p );
		$cpids = $this->_getChildProcess( $status['pid'] );

		// 親から止める
		@proc_terminate( $p );
		usleep(500*1000);
		@proc_terminate( $p );	// 2度送る

		foreach ( $cpids as $cpid )
		{
			$ret = posix_kill( $cpid, SIGTERM );		// sigterm
			usleep(100*1000);
			if ( ! $ret )
				posix_kill( $cpid, SIGKILL );	// sigkill
		}

		return true;	// 保証できない
	}

	/**
	 * 指定したプロセスIDが生成した子プロセスのpidリスト取得
	 * @param int $ppid プロセスID
	 * @return array pidリスト
	 */
	private function _getChildProcess( $ppid )
	{
		// ps を実行する
		$d = array(
				0 => array( 'file', '/dev/null', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'file', '/dev/null', 'w' ),
		);

		$ps = proc_open( '/bin/ps -o pid,ppid ax', $d, $pipes );
		do
		{
			$st = proc_get_status( $ps );
		}
		while ( $st['running'] );

		// 標準出力を読む
		$cpids = array();
		while ( ! feof( $pipes[1] ) )
		{
			$line = trim(fgets( $pipes[1] ));
			$pids = preg_split( "/[\s]+/", $line );
			if ( ! isset( $pids[1]) ) continue;
			if ( $pids[1] == $ppid )
			{
				array_push( $cpids, $pids[0] );
			}
		}
		fclose( $pipes[1] );
		proc_close( $ps );

		foreach ( $cpids as $p )
		{
			$ccpids = $this->_getChildProcess( $p );
			foreach ( $ccpids as $ccpid )
				array_push( $cpids, $ccpid );
		}

		return $cpids;
	}

	/**
	 * デストラクタ
	 */
	function __destruct()
	{
		msg_remove_queue( $this->msgh_r );	// メッセージハンドラ開放
		msg_remove_queue( $this->msgh_w );	// メッセージハンドラ開放
	}
}
?>
