<?php
class EpgrecProcMng
{
	protected $procQueue = array();

	// コマンド実行
	public static function execCommand( $cmd, $env = null)
	{
		$p = new EpgrecProc( $cmd, $env );
		return $p->startCommand();
	}

	// コマンドキュー追加
	public function addQueue( $proc )
	{
		if ( $proc instanceof EpgrecProc )
			$this->procQueue[] = $proc;
	}

	// コマンドキュー待ち
	public function waitQueue()
	{
		$counter = 0;
		do
		{
			$counter = 0;
			if ( count($this->procQueue) != 0 )
			{
				foreach( $this->procQueue as $proc )
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
		while( $counter != 0 );
	}
}
?>
