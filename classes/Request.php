<?php
/**
 * リクエスト変数クラス
 * @package Request
 */
class Request
{
	/**
	 * @var array POSTパラメータ
	 */
	private $_post;

	/**
	 * @var array GETパラメータ
	 */
	private $_query;

	/**
	 * @var array SESSIONパラメータ
	 */
	private $_session;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->_post = new Post();
		$this->_query = new QueryString();
		$this->_session = new Session();
	}

	/**
	 * POST変数取得
	 * @param string $key
	 * @return mixed
	 */
	public function getPost($key = null)
	{
		if ($key != null)
		{
			if ($this->_post->has($key))
			{
				return $this->_post->get($key);
			}
			else
			{
				return null;
			}
		}
		return $this->_post->get();
	}

	/**
	 * POST変数代入
	 * @param string $key
	 * @param mixed $value
	 */
	public function setPost($key, $value)
	{
		$this->_post->set($key, $value);
	}

	/**
	 * GET変数取得
	 * @param string $key
	 * @return mixed
	 */
	public function getQuery($key = null)
	{
		if ($key != null)
		{
			if ($this->_query->has($key))
			{
				return $this->_query->get($key);
			}
			else
			{
				return null;
			}
		}
		return $this->_query->get();
	}

	/**
	 * SESSION変数取得
	 * @param string $key
	 * @return mixed
	 */
	public function getSession($key = null)
	{
		if ($key != null)
		{
			if ($this->_session->has($key))
			{
				return $this->_session->get($key);
			}
			else
			{
				return null;
			}
		}
		return $this->_session->get();
	}

	/**
	 * SESSION変数代入
	 * @param string $key
	 * @param mixed $value
	 */
	public function setSession($key, $value)
	{
		$this->_session->set($key, $value);
	}

	/**
	 * SESSION変数保存
	 * @param string $key
	 */
	public function saveSession($key)
	{
		$this->_session->save($key);
	}
}
?>
