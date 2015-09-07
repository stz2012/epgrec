<?php
/**
 * コントローラ基底クラス
 * @package ControllerBase
 */
abstract class ControllerBase
{
	/**
	 * @var string システムルートパス
	 */
	protected $systemRoot;

	/**
	 * @var string コントローラ名
	 */
	protected $controller = 'index';

	/**
	 * @var string アクション名
	 */
	protected $action = 'index';

	/**
	 * @var array 各種設定
	 */
	protected $setting;

	/**
	 * @var object Modelクラス
	 */
	protected $model;

	/**
	 * @var object Viewクラス
	 */
	protected $view;

	/**
	 * @var object Requestクラス
	 */
	protected $request;

	/**
	 * @var object Tokenクラス
	 */
	protected $token;

	/**
	 * @var object Validateクラス
	 */
	protected $valid;

	/**
	 * @var array セッション情報
	 */
	protected $session = array();

	/**
	 * @var array エラーメッセージ
	 */
	protected $error_msg = array();

	/**
	 * @var string 次ページ情報
	 */
	protected $nextpage = null;

	/**
	 * @var string テンプレート情報
	 */
	protected $templateFile;

	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		$this->request = new Request();
		$this->token = new Token();
		$this->valid = new Validate();
	}

	/**
	 * システムのルートディレクトリパスを設定
	 * @param string $path パス文字列
	 */
	public function setSystemRoot($path)
	{
		$this->systemRoot = $path;
	}

	/**
	 * コントローラーとアクションの文字列設定
	 * @param string $controller コントローラ名
	 * @param string $action アクション名
	 */
	public function setControllerAction($controller, $action)
	{
		$this->controller = $controller;
		$this->action = $action;
	}

	/**
	 * コントローラ名を取得
	 * @return string コントローラ名
	 */
	public function getControllerName()
	{
		return $this->controller;
	}

	/**
	 * アクション名を取得
	 * @return string アクション名
	 */
	public function getActionName()
	{
		return $this->action;
	}

	/**
	 * 現在のURIを取得
	 * @param bool $with_action アクション名を付けるかどうか
	 * @param string $change_action 変更アクション名
	 * @return string URI文字列
	 */
	public function getCurrentUri($with_action=true, $change_action=null)
	{
		if ($with_action)
			return sprintf('%s%s/%s', BASE_URI, $this->controller, $this->action);
		else
		{
			if ($change_action != null)
				return sprintf('%s%s/%s', BASE_URI, $this->controller, $change_action);
			else
				return sprintf('%s%s', BASE_URI, $this->controller);
		}
	}

	/**
	 * 処理実行
	 */
	public function run()
	{
		try
		{
			// 設定の初期化
			$this->initializeSetting();

			// データベース接続情報設定
			ModelBase::setConnectionInfo($this->setting->getConnInfo());

			// モデルの初期化
			$this->initializeModel();

			// ビューの初期化
			$this->initializeView();
		}
		catch (Exception $e)
		{
			UtilLog::writeLog('初期化エラー：'.$e->getMessage());
			exit;
		}
		
		try
		{
			// 共通前処理
			$this->preAction();

			// 次ページへ遷移
			if ($this->nextpage != null)
			{
				ob_clean();
				header('Location: '.$this->nextpage);
				return;
			}

			// アクションメソッド
			$methodName = sprintf('%sAction', $this->action);
			$this->$methodName();

			// 共通後処理
			$this->postAction();

			// 次ページへ遷移
			if ($this->nextpage != null)
			{
				ob_clean();
				header('Location: '.$this->nextpage);
				return;
			}

			// 表示
			$this->view->display($this->templateFile);
		}
		catch(PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
			$this->_displayErrorPage($e);
		}
		catch (Exception $e)
		{
			UtilLog::writeLog($e->getMessage());
			$this->_displayErrorPage($e);
		}
	}

	/**
	 * 設定の初期化
	 */
	protected function initializeSetting()
	{
		$this->setting = Settings::factory();
	}

	/**
	 * モデルの初期化
	 */
	protected function initializeModel()
	{
		$className = ucfirst($this->controller) . 'Model';
		$classFile = sprintf('%s/models/%s.php', $this->systemRoot, $className);
		if (file_exists($classFile))
		{
			require_once $classFile;
			if (false == class_exists($className))
			{
				return;
			}
			$this->model = new $className();
			$this->model->setSetting($this->setting);
		}
		else
		{
			$this->model = new CommonModel();
			$this->model->setSetting($this->setting);
		}
	}

	/**
	 * ビューの初期化
	 */
	protected function initializeView()
	{
		// Smartyインスタンス作成
		$this->view = new Smarty();
		$this->view->template_dir = sprintf('%s/views/templates/', $this->systemRoot);
		$this->view->compile_dir = sprintf('%s/views/templates_c/', $this->systemRoot);
		// テンプレートファイル名を設定
		$this->templateFile = sprintf('%s/%s.tpl', $this->controller, $this->action);
	}

	/**
	 * 次ページを設定
	 * @param string $controller コントローラ名
	 * @param string $action アクション名
	 */
	protected function setNextPage($controller, $action=null)
	{
		if ($action != null)
			$this->nextpage = sprintf('%s%s/%s', BASE_URI, $controller, $action);
		else
			$this->nextpage = sprintf('%s%s', BASE_URI, $controller);
	}

	/**
	 * 共通前処理（オーバーライド前提）
	 */
	protected function preAction()
	{
		// コントローラ用のセッション変数を取得
		$this->session = $this->request->getSession($this->controller);
		// セッションタイムアウト判定
		if ($this->request->getQuery('SESS_ERROR') != null)
		{
			$this->request->setSession($this->controller, array());
			$this->request->saveSession($this->controller);
			$this->setNextPage('sorry', 'timeout');
		}
		// CSRF判定
		if ($this->request->getPost('token') != null && 
				$this->token->isCSRF($this->request->getPost('token')))
		{
			$this->request->setSession($this->controller, array());
			$this->request->saveSession($this->controller);
			$this->setNextPage('sorry', 'timeout');
		}
	}

	/**
	 * 共通後処理（オーバーライド前提）
	 */
	protected function postAction()
	{
		// デフォルトで割り当てるSmarty変数
		$this->view->assign('home_url',   HOME_URL);
		$this->view->assign('this_class', $this);
		$this->view->assign('post_data',  $this->request->getPost());
		$this->view->assign('sess_data',  $this->session);
		$this->view->assign('error_msg',  $this->error_msg);
		$this->view->assign('token',      $this->token->createToken());
		// テンプレート出力時のフィルター設定
		//$this->view->load_filter('output', 'trimwhitespace');
		// コントローラ用のセッション変数を保存
		$this->request->setSession($this->controller, $this->session);
		$this->request->saveSession($this->controller);
	}

	/**
	 * エラーページ表示
	 * @param object $e エラー情報
	 */
	private function _displayErrorPage($e)
	{
		$error_msg = $e->getMessage();
		$trace_str = print_r($e->getTrace(), true);
		$serv_str  = print_r($_SERVER, true);
		$post_str  = print_r($_POST, true);
		$sess_str  = print_r($_SESSION, true);
		$error_text =<<<HTML
【エラーメッセージ】
{$error_msg}

【スタックトレース】
{$trace_str}

【サーバ変数】
{$serv_str}

【POST変数】
{$post_str}

【セッションデータ】
{$sess_str}
HTML;
		ob_clean();
		echo  nl2br($error_text);
		exit;
	}
}
?>
