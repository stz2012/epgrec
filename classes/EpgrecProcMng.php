<?php
/**
 * Epgrecプロセス管理クラス
 */
class EpgrecProcMng
{
	/**
	 * @var array コマンドキュー
	 */
	protected $procQueue = array();

	/**
	 * コマンド実行
	 * @param string $cmd コマンド
	 * @param array $env 環境変数
	 */
	public static function execCommand( $cmd, $env = null)
	{
		$p = new EpgrecProc( $cmd, $env );
		return $p->startCommand();
	}

	/**
	 * コマンドキュー追加
	 * @param object $proc 追加するキュー
	 */
	public function addQueue( $proc )
	{
		if ( $proc instanceof EpgrecProc )
			$this->procQueue[] = $proc;
		else
			throw new Exception( 'EpgrecProcMng:: 不正なキュー形式です' );
	}

	/**
	 * コマンドキュー待ち
	 */
	public function waitQueue()
	{
		$counter = 0;
		do
		{
			$counter = 0;
			if ( count($this->procQueue) != 0 )
			{
				foreach ( $this->procQueue as $proc )
				{
					if ( $proc instanceof EpgrecProc && $proc->isRunning() )
					{
						$counter++;
						if ( ! $proc->isRunningSub() )
							break;
					}
				}
			}
			sleep(1);
		}
		while ( $counter != 0 );
	}
}
?>
