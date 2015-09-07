<?php
/**
 * トークン(CSRF対策)クラス
 * @package Token
 */
class Token
{
	/**
	 * @var int トークン名
	 */
	private $_ttl;
	
	/**
	 * @var int 有効期限
	 */
	private $_name;

	/**
	 * コンストラクタ
	 * @param string $name トークン名
	 * @param int $ttl 有効期限
	 */
	public function __construct($name = 'tokens', $ttl = 1800)
	{
		// セッションに登録するトークン配列の名称
		$this->_name = $name;

		// CSRF 検出トークン最大有効期限(秒)
		// 最小期限はこの値の 1/2 (1800 の場合は、900秒間は最低保持される)
		$this->_ttl = (int)$ttl;
	}

	/**
	 * トークンを生成
	 */
	public function createToken()
	{
		$curr = time();
		$tokens = isset( $_SESSION[$this->_name] ) ? $_SESSION[$this->_name] : array();
		foreach ( $tokens as $id => $time )
		{
			// 有効期限切れの場合はリストから削除
			if ( $time < $curr - $this->_ttl )
				unset( $tokens[$id] );
			else
				$uniq_id = $id;
		}
		if ( count( $tokens ) < 2 )
		{
			if ( ! $tokens || ( $curr - (int)( $this->_ttl / 2 ) ) >= max( $tokens ) )
			{
				$uniq_id = sha1( uniqid( rand(), TRUE ) );
				$tokens[$uniq_id] = time();
			}
		}
		// リストをセッションに登録
		$_SESSION[$this->_name] = $tokens;
		return $uniq_id;
	}

	/**
	 * セッションのリストにトークンが存在し、トークンが有効期限内の場合は FALSE を返す
	 * @param string $token トークンデータ
	 * @return bool トークンが有効かどうか
	 */
	public function isCSRF($token)
	{
		$tokens = $_SESSION[$this->_name];
		if ( isset( $tokens[$token] ) && $tokens[$token] > time() - $this->_ttl )
			return FALSE;
		else
			return TRUE;
	}
}
?>
