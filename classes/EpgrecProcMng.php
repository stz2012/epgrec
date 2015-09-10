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

	// デーモン作成
	public function createDaemon()
	{
		if ( pcntl_fork() != 0 )
			return false;
		posix_setsid();
		if ( pcntl_fork() != 0 )
			return false;
		pcntl_signal(SIGTERM, function($signo = 0) {
			// とりあえずシグナルは無視する
		});
		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);
		return true;
	}

	public function addQueue( $proc )
	{
		if ($proc instanceof EpgrecProc)
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
					if ($proc instanceof EpgrecProc && $proc->isRunning())
					{
						if ( $proc->isRunningSub() && $counter > 0)
							break;
						$counter++;
					}
				}
			}
		}
		while( $counter != 0 );
	}
}
?>
