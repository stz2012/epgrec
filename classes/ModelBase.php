<?php
/**
 * モデル基底クラス
 * @package ModelBase
 */
class ModelBase
{
	/**
	 * @var array 接続情報
	 */
	private static $connInfo;

	/**
	 * @var object 接続インスタンス
	 */
	private static $connInst = null;

	/**
	 * @var object PDOインスタンス
	 */
	protected $db = false;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->initDb();
	}

	/**
	 * ＤＢ初期化
	 */
	public function initDb()
	{
		if (self::isConnect())
		{
			$this->db = self::$connInst;
			//UtilLog::writeLog("PDOインスタンスの再利用: ".print_r(self::$connInst, true), 'DEBUG');
		}
		else
		{
			if (self::$connInfo['type'] == 'mysql')
			{
				$dsn = sprintf(
					'mysql:host=%s;port=%s;dbname=%s',
					self::$connInfo['host'],
					self::$connInfo['port'],
					self::$connInfo['dbname']
				);
				try
				{
					$this->db = new PDO($dsn, self::$connInfo['dbuser'], self::$connInfo['dbpass'], 
						array(
								PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8 COLLATE 'utf8_general_ci'",
								PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode=''"
						)
					);
				}
				catch (Exception $e)
				{
					return;
				}
				// クエリのバッファリングを強制する
				$this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
				// 自動コミットをOff
				//$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
			}
			else if (self::$connInfo['type'] == 'pgsql')
			{
				$dsn = sprintf(
					'pgsql:host=%s;port=%s;dbname=%s;username=%s;password=%s',
					self::$connInfo['host'],
					self::$connInfo['port'],
					self::$connInfo['dbname'],
					self::$connInfo['dbuser'],
					self::$connInfo['dbpass']
				);
				try
				{
					$this->db = new PDO($dsn);
				}
				catch (Exception $e)
				{
					return;
				}
				// 自動コミットをOff
				//$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
			}
			else if (self::$connInfo['type'] == 'sqlite')
			{
				$dsn = sprintf(
					'sqlite:%s',
					self::$connInfo['dbname']
				);
				try
				{
					$this->db = new PDO($dsn);
				}
				catch (Exception $e)
				{
					return;
				}
			}
			else
				throw new Exception('接続パラメータ不正');
			// カラム名を小文字で取得する
			$this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
			// NULLを空文字に変換する
			$this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
			// エラー時にExceptionをthrowさせる
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// PDOインスタンスを保存
			self::$connInst = $this->db;
			//UtilLog::writeLog("PDOインスタンスの初期生成: ".print_r(self::$connInst, true), 'DEBUG');
		}
	}

	/**
	 * 接続情報を設定
	 * @param array $connInfo
	 */
	public static function setConnectionInfo($connInfo)
	{
		self::$connInfo = $connInfo;
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
	 * 件数を取得
	 * @param string $fields
	 * @param string $tableName
	 * @param mixed $whereData
	 * @return int
	 */
	public function selectCount($fields, $tableName, $whereData)
	{
		$where = $this->wherePrepare($whereData);
		$stmt = $this->db->prepare("SELECT COUNT({$fields})
									FROM {$tableName} {$where}");
		$this->whereBindValue($whereData, $stmt);
		if ($stmt->execute() !== false)
		{
			$tmp_arr = $stmt->fetch(PDO::FETCH_NUM);
			$ret_cnt = $tmp_arr[0];
		}
		else
			$ret_cnt = 0;
		$stmt->closeCursor();
		return $ret_cnt;
	}

	/**
	 * 行データを取得
	 * @param string $fields
	 * @param string $tableName
	 * @param mixed $whereData
	 * @param mixed $orderData
	 * @param mixed $limitData
	 * @return array
	 */
	public function selectRow($fields, $tableName, $whereData, $orderData=null, $limitData=null)
	{
		$where = $this->wherePrepare($whereData);
		$order = $this->orderPrepare($orderData);
		$limit = $this->limitPrepare($limitData);
		$stmt = $this->db->prepare("SELECT {$fields}
									FROM {$tableName} {$where} {$order} {$limit}");
		$this->whereBindValue($whereData, $stmt);
		if ($stmt->execute() !== false)
			$ret_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		else
			$ret_data = array();
		$stmt->closeCursor();
		return $ret_data;
	}

	/**
	 * 行データを削除
	 * @param string $tableName
	 * @param mixed $whereData
	 * @return int
	 */
	public function deleteRow($tableName, $whereData)
	{
		$where = $this->wherePrepare($whereData);
		$stmt = $this->db->prepare("DELETE FROM {$tableName} {$where}");
		$this->whereBindValue($whereData, $stmt);
		if ($stmt->execute() !== false)
			$ret_cnt = $stmt->rowCount();
		else
			$ret_cnt = 0;
		$stmt->closeCursor();
		return $ret_cnt;
	}

	/**
	 * 行データを挿入
	 * @param string $tableName
	 * @param array $bindData
	 * @return int
	 */
	public function insertRow($tableName, $bindData)
	{
		$insertData = $this->_insertPrepare($bindData);
		$stmt = $this->db->prepare("INSERT INTO
									{$tableName} ({$insertData['fields']})
									VALUES({$insertData['placeholders']})");
		foreach ($bindData as $key => $val)
		{
			$param = $this->defineParamType($val);
			$stmt->bindValue(":ins_{$key}", $val, $param);
		}
		if ($stmt->execute() !== false)
			$ret_cnt = $stmt->rowCount();
		else
			$ret_cnt = 0;
		$stmt->closeCursor();
		return $ret_cnt;
	}

	/**
	 * 行データを更新
	 * @param string $tableName
	 * @param array $bindData
	 * @param mixed $whereData
	 * @return int
	 */
	public function updateRow($tableName, $bindData, $whereData)
	{
		$updateData = $this->_updatePrepare($bindData);
		$where = $this->wherePrepare($whereData);
		$stmt = $this->db->prepare("UPDATE {$tableName}
									SET {$updateData} {$where}");
		foreach ($bindData as $key => $val)
		{
			$param = $this->defineParamType($val);
			$stmt->bindValue(":upd_{$key}", $val, $param);
		}
		$this->whereBindValue($whereData, $stmt);
		if ($stmt->execute() !== false)
			$ret_cnt = $stmt->rowCount();
		else
			$ret_cnt = 0;
		$stmt->closeCursor();
		return $ret_cnt;
	}

	/**
	 * PDOパラメータタイプを所得
	 * @param mixed $val
	 * @return int|null
	 */
	protected function defineParamType($val)
	{
		switch ($val)
		{
			case (is_int($val)):
				$param = PDO::PARAM_INT;
				break;
			case (is_string($val)):
				$param = PDO::PARAM_STR;
				break;
			case (is_bool($val)):
				$param = PDO::PARAM_BOOL;
				break;
			case (is_Null($val)):
				$param = PDO::PARAM_Null;
				break;
			default:
				$param = Null;
		}
		return $param;
	}

	/**
	 * WHERE句のプリペアード
	 * @param mixed $whereData
	 * @param string $prefix
	 * @return string
	 */
	protected function wherePrepare($whereData, $prefix=Null)
	{
		if ($whereData == Null || 
				(is_array($whereData) && count($whereData) == 0) || 
					(!is_array($whereData) && trim($whereData) == ''))
			return '';
		$placeHolders = ($prefix != Null) ? " {$prefix} " : ' WHERE ';
		if (is_array($whereData))
		{
			ksort($whereData);
			foreach ($whereData as $key => $val)
			{
				if (is_array($val))
				{
					$find_keys = preg_grep('/^(operator|value)$/is', array_keys($val));
					if (count($find_keys) == 2)
					{
						if (preg_match('/^between/is', $val['operator']))
							$placeHolders .= " {$key} {$val['operator']} :where_{$key}_min AND :where_{$key}_max AND";
						else
							$placeHolders .= " {$key} {$val['operator']} :where_{$key} AND";
					}
					else
					{
						$placeHolders .= " {$key} IN (";
						foreach ($val as $key2 => $val2)
						{
							$placeHolders .= ":where_{$key}_{$key2},";
						}
						$placeHolders = rtrim($placeHolders, ',');
						$placeHolders .= ") AND";
					}
				}
				else
				{
					$placeHolders .= " {$key}=:where_{$key} AND";
				}
			}
			$placeHolders = rtrim($placeHolders, ' AND');
		}
		else
			$placeHolders .= $whereData;
		return $placeHolders;
	}

	/**
	 * WHERE句のバインド
	 * @param mixed $whereData
	 * @param resource $stmt
	 */
	protected function whereBindValue($whereData, &$stmt)
	{
		if ($whereData == Null || 
				(is_array($whereData) && count($whereData) == 0) || 
					(!is_array($whereData) && trim($whereData) == ''))
			return;
		if (is_array($whereData))
		{
			ksort($whereData);
			foreach ($whereData as $key => $val)
			{
				if (is_array($val))
				{
					$find_keys = preg_grep('/^(operator|value)$/is', array_keys($val));
					if (count($find_keys) == 2)
					{
						if (preg_match('/^like/is', $val['operator']))
							$stmt->bindValue(":where_{$key}", "%{$val['value']}%", PDO::PARAM_STR);
						else if (preg_match('/^between/is', $val['operator']))
						{
							$param = $this->defineParamType($val['value'][0]);
							$stmt->bindValue(":where_{$key}_min", $val['value'][0], $param);
							$param = $this->defineParamType($val['value'][1]);
							$stmt->bindValue(":where_{$key}_max", $val['value'][1], $param);
						}
						else
						{
							$param = $this->defineParamType($val['value']);
							$stmt->bindValue(":where_{$key}", $val['value'], $param);
						}
					}
					else
					{
						foreach ($val as $key2 => $val2)
						{
							$param = $this->defineParamType($val2);
							$stmt->bindValue(":where_{$key}_{$key2}", $val2, $param);
						}
					}
				}
				else
				{
					$param = $this->defineParamType($val);
					$stmt->bindValue(":where_{$key}", $val, $param);
				}
			}
		}
	}

	/**
	 * ORDER句のプリペアード
	 * @param mixed $orderData
	 * @param string $prefix
	 * @return string
	 */
	protected function orderPrepare($orderData, $prefix=Null)
	{
		if ($orderData == Null || 
				(is_array($orderData) && count($orderData) == 0) || 
					(!is_array($orderData) && trim($orderData) == ''))
			return '';
		$placeHolders = ($prefix != Null) ? " {$prefix} " : ' ORDER BY ';
		if (is_array($orderData))
		{
			foreach ($orderData as $val)
			{
				if (is_array($val) && count($val) == 2)
					$placeHolders .= "{$val[0]} {$val[1]}, ";
				else
					$placeHolders .= "$val, ";
			}
			$placeHolders = rtrim($placeHolders, ', ');
		}
		else
			$placeHolders .= $orderData;
		return $placeHolders;
	}

	/**
	 * LIMIT句のプリペアード
	 * @param mixed $limitData
	 * @param string $prefix
	 * @return string
	 */
	protected function limitPrepare($limitData, $prefix=Null)
	{
		if ($limitData == Null || 
				(is_array($limitData) && count($limitData) == 0) || 
					(!is_array($limitData) && trim($limitData) == ''))
			return '';
		$placeHolders = ($prefix != Null) ? " {$prefix} " : ' LIMIT ';
		if (is_array($limitData))
		{
			if (count($limitData) == 2)
			{
				if (self::$connInfo['type'] == 'mysql')
					$placeHolders .= "{$limitData[0]}, {$limitData[1]}";
				else
					$placeHolders = "OFFSET {$limitData[0]} LIMIT {$limitData[1]}";
			}
			else
				$placeHolders .= $limitData[0];
		}
		else
			$placeHolders .= $limitData;
		return $placeHolders;
	}

	/**
	 * INSERT句のプリペアード
	 * @param array $bindData
	 */
	private function _insertPrepare($bindData)
	{
		ksort($bindData);
		$insertArray = array(
			'fields'       => implode(",", array_keys($bindData)),
			'placeholders' => ':ins_' . implode(',:ins_', array_keys($bindData))
		);
		return $insertArray;
	}

	/**
	 * UPDATE句のプリペアード
	 * @param array $bindData
	 */
	private function _updatePrepare($bindData)
	{
		ksort($bindData);
		$placeHolders = Null;
		foreach ($bindData as $key => $val)
		{
			$placeHolders .= "$key=:upd_$key, ";
		}
		$placeHolders = rtrim($placeHolders, ', ');
		return $placeHolders;
	}
}
?>
