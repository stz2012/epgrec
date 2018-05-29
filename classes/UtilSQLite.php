<?php
/**
 * SQLiteユーティリティ
 * @package Util
 * @subpackage UtilSQLite
 */
class UtilSQLite
{
	/**
	 * @var object 接続インスタンス
	 */
	protected static $connInst = null;

	/**
	 * @var object PDOインスタンス
	 */
	protected $db = false;

	/**
	 * @var array ログ種別
	 */
	protected static $logType = array(
		'chkstatus',
		'encode',
		'recorder',
		'shutdown',
		'wakeup'
	);

	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		if (self::isConnect())
		{
			$this->db = self::$connInst;
			//UtilLog::writeLog("PDOインスタンスの再利用: ".print_r(self::$connInst, true), 'DEBUG');
			return;
		}

		$initDb = false;
		if (file_exists(DB_FILEPATH))
		{
			if (filesize(DB_FILEPATH) == 0)
				$initDb = true;
		}
		else
			$initDb = true;

		try
		{
			$this->db = new PDO('sqlite:'.DB_FILEPATH);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if ($initDb)
			{
				// ＤＢ初期スクリプト（設定）
				$sql = <<<SQL_TEXT
DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `sid`        INTEGER PRIMARY KEY AUTOINCREMENT,
  `item_name`  VARCHAR NOT NULL,
  `item_value` TEXT NOT NULL
);
SQL_TEXT;
				$this->db->exec($sql);

				// ＤＢ初期スクリプト（イベント）
				foreach ( self::$logType as $type_name )
				{
					$sql = <<<SQL_TEXT
DROP TABLE IF EXISTS `{$type_name}`;
CREATE TABLE `{$type_name}` (
  `event_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `event_date` TIMESTAMP DEFAULT (DATETIME('now','localtime')),
  `event_comment` varchar(512) DEFAULT NULL
);
SQL_TEXT;
					$this->db->exec($sql);
				}

				// 暗号化キー生成
				$sql = "INSERT INTO setting (";
				$sql .= "item_name, item_value";
				$sql .= ") VALUES (";
				$sql .= "?, ?";
				$sql .= ")";
				$stmt = $this->db->prepare($sql);
				$stmt->bindValue(1, 'CRYPT_KEY');
				$stmt->bindValue(2, UtilString::getRandomString(32));
				$stmt->execute();
				$stmt->closeCursor();

				// 設定XML生成
				$sql = "INSERT INTO setting (";
				$sql .= "item_name, item_value";
				$sql .= ") VALUES (";
				$sql .= "?, ?";
				$sql .= ")";
				$stmt = $this->db->prepare($sql);
				$stmt->bindValue(1, 'OPTION_XML');
				$stmt->bindValue(2, Settings::getDefaults()->asXML());
				$stmt->execute();
				$stmt->closeCursor();
			}
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}
	}

	/**
	 * 接続状態を判定
	 * @return bool
	 */
	public static function isConnect()
	{
		return (self::$connInst != null);
	}

	/**
	 * 暗号化キーを取得
	 * @return string
	 */
	public static function getCryptKey()
	{
		$retval = '';

		try
		{
			$db_obj = new self();
			$sql = "SELECT item_value FROM setting";
			$sql .= " WHERE item_name = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, 'CRYPT_KEY');
			$stmt->execute();
			$result = $stmt->fetchColumn();
			if ($result !== false)
				$retval = $result;
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * 設定XMLを取得
	 * @return string
	 */
	public static function getOptionXml()
	{
		$retval = '';

		try
		{
			$db_obj = new self();
			$sql = "SELECT item_value FROM setting";
			$sql .= " WHERE item_name = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, 'OPTION_XML');
			$stmt->execute();
			$result = $stmt->fetchColumn();
			if ($result !== false)
				$retval = $result;
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * 設定XMLを更新
	 * @param string $xml XML文字列
	 * @return bool
	 */
	public static function updOptionXml($xml)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "UPDATE setting SET item_value = ?";
			$sql .= " WHERE item_name = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $xml);
			$stmt->bindValue(2, 'OPTION_XML');
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * 何時間以内にイベントが存在するかどうか
	 * @param string $table_name 
	 * @param int $minutes 
	 * @return bool
	 */
	public static function isExistEventWithInHours($table_name, $hours)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "SELECT COUNT(event_id)";
			$sql .= " FROM {$table_name}";
			$sql .= " WHERE DATETIME(event_date) > DATETIME('now', '-{$hours} hours', 'localtime')";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->execute();
			$cnt = $stmt->fetchColumn();
			$stmt->closeCursor();
			$retval = ($cnt > 0);
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ログ種別取得
	 * @return array
	 */
	public static function getLogType()
	{
		$retval = array();
		foreach ( self::$logType as $val )
		{
			switch ( $val )
			{
				case 'chkstatus':
					$retval[$val] = '状態チェックログ';
					break;
				case 'encode':
					$retval[$val] = 'エンコードログ';
					break;
				case 'recorder':
					$retval[$val] = 'レコーダーログ';
					break;
				case 'shutdown':
					$retval[$val] = 'ＰＣ終了ログ';
					break;
				case 'wakeup':
					$retval[$val] = 'ＰＣ起動ログ';
					break;
			}
		}
		return $retval;
	}

	/**
	 * イベントログ取得
	 * @param string $table_name 
	 * @return array
	 */
	public static function getEventLog($table_name)
	{
		$retval = '';

		try
		{
			$db_obj = new self();
			$sql = "SELECT * FROM {$table_name}";
			$sql .= " ORDER BY event_date DESC";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->execute();
			$retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * イベントログ出力
	 * @param string $table   テーブル名
	 * @param string $comment コメント
	 * @return bool
	 */
	public static function outEventLog($table, $comment)
	{
		$retval = false;
		$comment = mb_convert_encoding($comment, 'UTF-8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS');

		try
		{
			$db_obj = new self();
			$sql = "INSERT INTO ".strtolower($table)." (event_comment)";
			$sql .= " VALUES (?)";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $comment);
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * イベントログ消去
	 */
	public static function cleanEventLog()
	{
		try
		{
			$db_obj = new self();
			foreach ( self::$logType as $type_name )
			{
				$sql = "DELETE FROM {$type_name}";
				$sql .= " WHERE datetime(event_date) < datetime('now', '-10 days', 'localtime')";
				$stmt = $db_obj->db->prepare($sql);
				$stmt->execute();
			}
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}
	}
}
?>