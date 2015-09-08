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

	public function addQueue( $cmd )
	{
		$this->procQueue[] = new EpgrecProc( $cmd );
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
					if ( $proc->isRunning() ) $counter++;
				}
			}
		} while( $counter != 0 );
	}

	// デーモン作成
	public static function createDaemon()
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
	}
}
?>
