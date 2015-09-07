<?php
/**
 * ディスパッチャクラス
 * @package Dispatcher
 */
class Dispatcher
{
	/**
	 * @var string システムルートパス
	 */
	private $_sysRoot;

	/**
	 * システムのルートディレクトリを設定
	 * @param string $path パス文字列
	 */
	public function setSystemRoot($path)
	{
		$this->_sysRoot = rtrim($path, '/');
	}

	/**
	 * 振分け処理実行
	 */
	public function dispatch()
	{
		// パラメーター取得（先頭の / を削除）
		$param = preg_replace('|^'.preg_quote(BASE_URI).'|u', '', $_SERVER['REQUEST_URI']);

		// パラメーター取得（末尾の / を削除）
		$param = preg_replace('|/$|u', '', parse_url($param, PHP_URL_PATH));

		$params = array();
		if ($param != '')
		{
			// パラメーターを / で分割
			$params = explode('/', $param);
		}

		// １番目のパラメーターをコントローラーとして取得
		$controller = 'index';
		if (count($params) > 0)
		{
			$controller = preg_replace('/[^a-z_A-Z0-9]/u', '', $params[0]);
		}

		// コントローラークラスインスタンス取得
		$controllerInstance = $this->_getControllerInstance($controller);
		if (null == $controllerInstance)
		{
			// コントローラ名をアクションに変換して再試行
			$action = $controller;
			$controller = 'index';
			$controllerInstance = $this->_getControllerInstance($controller);
		}
		else
		{
			// 2番目のパラメーターをアクションとして取得
			$action = 'index';
			if (count($params) > 1)
			{
				$action = preg_replace('/[^a-z_A-Z0-9]/u', '', $params[1]);
			}
		}

		// アクションメソッドの存在確認
		if (!method_exists($controllerInstance, $action . 'Action'))
		{
			header('HTTP', true, 404);
			exit;
		}

		// コントローラー初期設定
		$controllerInstance->setSystemRoot($this->_sysRoot);
		$controllerInstance->setControllerAction($controller, $action);

		// 処理実行
		$controllerInstance->run();
	}

	/**
	 * コントローラークラスのインスタンスを取得
	 * @param string $controller コントローラ名
	 */
	private function _getControllerInstance($controller)
	{
		// 一文字目のみ大文字に変換＋"Controller"
		$className = ucfirst(strtolower($controller)) . 'Controller';
		// コントローラーファイル名
		$controllerFileName = sprintf(
			'%s/controllers/%s.php',
			$this->_sysRoot,
			$className
		);
		// ファイル存在チェック
		if (!file_exists($controllerFileName))
		{
			return null;
		}
		// クラスファイルを読込
		require_once $controllerFileName;
		// クラスが定義されているかチェック
		if (!class_exists($className))
		{
			return null;
		}
		// クラスインスタンス生成
		$controllerInstarnce = new $className();
		return $controllerInstarnce;
	}
}
?>
