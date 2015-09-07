<?php
/**
 * リクエスト変数抽象クラス
 * @package RequestVariables
 */
abstract class RequestVariables
{
	/**
	 * @var array 保持する値
	 */
	protected $_values;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->setValues();
	}

	/**
	 * パラメータ値設定
	 */
	abstract protected function setValues();

	/**
	 * 指定キーのパラメータを取得
	 * @param string $key
	 * @return mixed
	 */
	public function get($key = null)
	{
		$ret = null;
		if ($key == null)
		{
			$ret = $this->_values;
		}
		else
		{
			$array = $this->_values;
			foreach (explode('.', $key) as $key_part)
			{
				if (!is_array($array) || !array_key_exists($key_part, $array))
				{
					$ret = null;
					break;
				}
				$ret = $array = $array[$key_part];
			}
		}
		return $ret;
	}

	/**
	 * 指定キーのパラメータへ代入
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value)
	{
		$array = &$this->_values;
		$keys = explode('.', $key);
		while (count($keys) > 1)
		{
			$key = array_shift($keys);
			if (!isset($array[$key]) || !is_array($array[$key]))
			{
				$array[$key] = array();
			}
			$array = &$array[$key];
		}
		$array[array_shift($keys)] = $value;
	}

	/**
	 * 指定のキーが存在するか確認
	 * @param string $key
	 */
	public function has($key)
	{
		$array = $this->_values;
		foreach (explode('.', $key) as $key_part)
		{
			if (!is_array($array) || !array_key_exists($key_part, $array))
			{
				return false;
			}
			$array = $array[$key_part];
		}
		return true;
	}
}
?>
