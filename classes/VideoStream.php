<?php
/**
 * 動画ストリーミングクラス
 *
 * @author Rana
 * @link http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 */
class VideoStream
{
	private $path = "";
	private $stream = "";
	private $buffer = 102400;
	private $start  = -1;
	private $end    = -1;
	private $size   = 0;

	/**
	 * コンストラクタ
	 * @param string $filePath ファイルパス
	 */
	function __construct($filePath)
	{
		$this->path = $filePath;
	}

	/**
	* ファイルオープン
	*/
	private function open()
	{
		if (!($this->stream = fopen($this->path, 'rb')))
		{
			throw new Exception('Could not open stream for reading');
		}
	}

	/**
	* HTTPヘッダ設定
	*/
	private function setHeader()
	{
		$this->start = 0;
		$this->size  = @filesize($this->path);
		if ($this->size <= 0)
		{
			ob_start();
			system('ls -al "'.$this->path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
			$this->size = ob_get_clean();
		}
		$this->end = $this->size - 1;

		ob_get_clean();
		header("Content-Type: video/mpeg");
		header("Cache-Control: max-age=2592000, public");
		header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
		header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
		header("Accept-Ranges: 0-{$this->end}");

		if (isset($_SERVER['HTTP_RANGE']))
		{
			$c_start = $this->start;
			$c_end = $this->end;
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

			if (strpos($range, ',') !== false)
			{
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes {$this->start}-{$this->end}/{$this->size}");
				exit;
			}

			if ($range == '-')
			{
				$c_start = $this->size - substr($range, 1);
			}
			else
			{
				$range = explode('-', $range);
				$c_start = $range[0];
				$c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
			}
			$c_end = ($c_end > $this->end) ? $this->end : $c_end;

			if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size)
			{
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes {$this->start}-{$this->end}/{$this->size}");
				exit;
			}

			$this->start = $c_start;
			$this->end = $c_end;
			$length = $this->end - $this->start + 1;
			fseek($this->stream, $this->start);
			header('HTTP/1.1 206 Partial Content');
			header("Content-Length: {$length}");
			header("Content-Range: bytes {$this->start}-{$this->end}/{$this->size}");
		}
		else
		{
			header("Content-Length: {$this->size}");
		}
	}

	/**
	* データストリーム
	*/
	private function stream()
	{
		$i = $this->start;
		set_time_limit(0);
		while (!feof($this->stream) && $i <= $this->end)
		{
			$bytesToRead = $this->buffer;
			if (($i+$bytesToRead) > $this->end)
			{
				$bytesToRead = $this->end - $i + 1;
			}
			$data = fread($this->stream, $bytesToRead);
			echo $data;
			flush();
			$i += $bytesToRead;
		}
	}

	/**
	* ファイルクローズ
	*/
	private function close()
	{
		fclose($this->stream);
	}

	/**
	* ストリーミング実行
	*/
	public function run()
	{
		$this->open();
		$this->setHeader();
		$this->stream();
		$this->close();
	}
}
?>