<?php
/**
 * Epgrecプロセスクラス
 */
class EpgrecProc
{
	/**
	 * @var string コマンド
	 */
	private $procCmd;

	/**
	 * @var array 環境変数
	 */
	private $procEnv;

	/**
	 * @var resource プロセスリソース
	 */
	private $procRes = null;

	/**
	 * @var int プロセスID
	 */
	private $procId = -1;

	/**
	 * @var array サブプロセス
	 */
	private $procSub = array();

	/**
	 * @var bool メインプロセスが実行中かどうか
	 */
	private $isRunMain = false;

	/**
	 * @var bool サブプロセスが実行中かどうか
	 */
	private $isRunSub = false;

	/**
	 * コンストラクタ
	 * @param string $cmd コマンド
	 * @param array $env 環境変数
	 */
	function __construct( $cmd, $env = null )
	{
		$this->procCmd = $cmd;
		$this->procEnv = $env;
	}

	/**
	 * コマンド開始
	 * @return mixed 
	 */
	public function startCommand()
	{
		$descSpec = array(
			0 => array( 'file', '/dev/null', 'r' ),
			1 => array( 'file', '/dev/null', 'w' ),
			2 => array( 'file', '/dev/null', 'w' ),
		);
		if ( $this->procRes == null )
		{
			$this->procRes = proc_open( $this->procCmd, $descSpec, $pipes, INSTALL_PATH, $this->procEnv );
			//UtilLog::writeLog("EpgrecProc::startCommand PID={$this->getPID()} 開始", 'DEBUG');
			$this->isRunMain = true;
		}
		if ( is_resource( $this->procRes ) )
			return $this->procRes;
		else
			return false;
	}

	/**
	 * コマンド終了待ち
	 * @return bool true: 正常終了、false: 異常終了
	 */
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
					//UtilLog::writeLog("EpgrecProc::waitCommand PID={$status['pid']} 終了", 'DEBUG');
					$this->isRunMain = false;
				}
				break;
			}
			sleep(1);
		}
		proc_close( $this->procRes );
		return true;
	}

	/**
	 * サブコマンド追加
	 * @param string $cmd コマンド
	 */
	public function addSubCmd( $cmd )
	{
		$this->procSub[] = new self( $cmd );
	}

	/**
	 * プロセスID取得
	 * @return int 
	 */
	public function getPID()
	{
		if ( $this->procId == -1 && is_resource( $this->procRes ) )
		{
			$status = proc_get_status( $this->procRes );
			$this->procId = $status['pid'];
		}
		return $this->procId;
	}

	/**
	 * 実行中かどうか
	 * @return bool 
	 */
	public function isRunning()
	{
		if ( ! $this->startCommand() )
			return false;
		$status = proc_get_status( $this->procRes );
		if ( ! $status['running'] )
		{
			if ( $this->isRunMain )
			{
				//UtilLog::writeLog("EpgrecProc::isRunning PID={$status['pid']} 終了", 'DEBUG');
				$this->isRunMain = false;
			}
			if ( count($this->procSub) != 0 )
			{
				$this->isRunSub = false;
				foreach ( $this->procSub as $proc )
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

	/**
	 * サブコマンド実行中かどうか
	 * @return bool 
	 */
	public function isRunningSub()
	{
		return $this->isRunSub;
	}
}
?>
