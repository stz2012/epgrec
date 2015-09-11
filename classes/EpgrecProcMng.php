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

	public function addQueue( $proc )
	{
		if ( $proc instanceof EpgrecProc )
			$this->procQueue[] = $proc;
	}

	public function waitQueue()
	{
		$counter = 0;
		do
		{
			sleep(1);
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
		}
		while( $counter != 0 );
	}
}
?>
