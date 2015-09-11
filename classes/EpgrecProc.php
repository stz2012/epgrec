<?php
class EpgrecProc
{
	private $procCmd;
	private $procEnv;
	private $procRes = null;
	private $procId = -1;
	private $procSub = array();
	private $isRunMain = false;
	private $isRunSub = false;

	// コンストラクタ
	function __construct( $cmd, $env = null )
	{
		$this->procCmd = $cmd;
		$this->procEnv = $env;
	}

	// コマンド開始
	public function startCommand()
	{
		$descspec = array(
			0 => array( 'file', '/dev/null', 'r' ),
			1 => array( 'file', '/dev/null', 'w' ),
			2 => array( 'file', '/dev/null', 'w' ),
		);
		if ( $this->procRes == null )
		{
			$this->procRes = proc_open( $this->procCmd, $descspec, $pipes, INSTALL_PATH, $this->procEnv );
			//UtilLog::writeLog("EpgrecProc.php: PID={$this->getPID()} 開始", 'DEBUG');
			$this->isRunMain = true;
		}
		if ( is_resource( $this->procRes ) )
			return $this->procRes;
		else
			return false;
	}

	// コマンド実行待ち
	public function waitCommand()
	{
		if ( ! $this->startCommand() )
			return false;
		while (1)
		{
			$status = proc_get_status( $this->procRes );
			if ( ! $status['running'] )
			{
				if ( $this->isRunMain )
				{
					//UtilLog::writeLog("EpgrecProc.php: PID={$status['pid']} 終了", 'DEBUG');
					$this->isRunMain = false;
				}
				break;
			}
			sleep(1);
		}
		proc_close( $this->procRes );
		return true;
	}

	// サブコマンド追加
	public function addSubCmd( $cmd )
	{
		$this->procSub[] = new self( $cmd );
	}

	// プロセスID取得
	public function getPID()
	{
		if ( $this->procId == -1 && is_resource( $this->procRes ) )
		{
			$status = proc_get_status( $this->procRes );
			$this->procId = $status['pid'];
		}
		return $this->procId;
	}

	// 実行中かどうか
	public function isRunning()
	{
		if ( ! $this->startCommand() )
			return false;
		$status = proc_get_status( $this->procRes );
		if ( ! $status['running'] )
		{
			if ( $this->isRunMain )
			{
				//UtilLog::writeLog("EpgrecProc.php: PID={$status['pid']} 終了", 'DEBUG');
				$this->isRunMain = false;
			}
			if ( count($this->procSub) != 0 )
			{
				$this->isRunSub = false;
				foreach( $this->procSub as $proc )
				{
					if ( $proc instanceof EpgrecProc && $proc->isRunning() )
					{
						$this->isRunSub = true;
						break;
					}
				}
				if ( $this->isRunSub )
					return true;
			}
			return false;
		}
		return true;
	}

	// サブコマンド実行中かどうか
	public function isRunningSub()
	{
		return $this->isRunSub;
	}
}
?>
