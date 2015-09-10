<?php
class EpgrecProc
{
	private $procCmd;
	private $procEnv;
	private $procRes = null;
	private $procSub = array();
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
			0 => array( 'file','/dev/null','r' ),
			1 => array( 'file','/dev/null','w' ),
			2 => array( 'file','/dev/null','w' ),
		);
		$this->procRes = proc_open( $this->procCmd, $descspec, $pipes, INSTALL_PATH, $this->procEnv );
		if ( is_resource( $this->procRes ) )
			return $this->procRes;
		else
			return false;
	}

	// コマンド実行待ち
	public function waitCommand()
	{
		if ( $this->startCommand() !== false )
		{
			while (1);
			{
				$status = proc_get_status( $this->procRes );
				if ( $status['running'] === false )
					break;
				sleep(1);
			}
			return true;
		}
		return false;
	}

	public function isRunning()
	{
		if ($this->procRes == null)
			return ($this->startCommand() !== false);
		$status = proc_get_status( $this->procRes );
		UtilLog::writeLog("isRunning: ".print_r($status, true), 'DEBUG');
		if ( $status['running'] === false )
		{
			if ( count($this->procSub) != 0 )
			{
				foreach( $this->procSub as $proc )
				{
					if ($proc instanceof EpgrecProc && $proc->isRunning())
					{
						$this->isRunSub = true;
						return true;
					}
				}
			}
			return false;
		}
		return true;
	}

	public function isRunningSub()
	{
		return $this->isRunSub;
	}

	public function addSubCmd( $cmd )
	{
		$this->procSub[] = new self( $cmd );
	}
}
?>
