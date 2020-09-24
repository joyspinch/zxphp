<?php
namespace Ctrl;

// hook controller_use.php

/**
 * Class Controller
 *
 * @package Ctrl
 * @property \Request              $request
 * @property \swoole_http_response $_response
 * @property \Server\App           $app
 */
Class Controller extends \Server\Libs\Controller
{
	// hook controller_public_start.php

	public $token = [];
	public $session_id = '';
	public $request = [];
	public $route = [];
	public $assign = [];
	public $is_ajax = '';
	public $_method = '';

	// hook controller_public_end.php
	public function __construct($server, $route, \Request $request, $response)
	{
		parent::__construct($server, $route, $request, $response);

		$this->assign['role_kv'] = $this->Role->role_kv;
		$this->UserInfo();
	}

	// hook controller_start.php

	public function DestroyToken($name = '')
	{
		empty($name) && $name = _CONF('session_id');
		// hook controller_destroytoken_start.php
		$this->setcookie($name,'',-1000);
		// hook controller_destroytoken_end.php
	}

	public function UserInfo($name = '', $url = '', $open_auth = 0)
	{
		empty($name) && $name = _CONF('session_id');
		// hook controller_userinfo_start.php
		empty($url) && $url = '../../' . $this->route[0] . '/index/login';
		$route = implode('_', $this->route);
		$k = strtolower($route . '_' . $this->_method);

		if (empty($_ENV['auth'][$k])) {
			$k = strtolower($route);
			$auth = $_ENV['auth'][$k];
		} else {
			$auth = $_ENV['auth'][$k];
		}
		$this->session_id = $this->request->cookie(_CONF('cookie_tablepre') . $name);
		$user = $this->User->check_token($this->request, $this->session_id);
		if (_CONF('site_closed') == "false" && $this->not_closed == 0 && $user['gid'] != 1) {
			$this->response('9999', [], '站点关闭');
		}
		if ((!empty($auth['is_auth']) && !empty($auth['is_login'])) || !empty($open_auth)) {

			if (empty($user['uid'])) {
				if ($this->is_ajax) {
					$this->response('0401');
				} else {
					$this->response('0401', [], '', $url, 302);
				}
			}

			$au = $this->RoleAuth->read(['role_id' => $user['gid'], 'node' => $k]);
			if (empty($au['role_id']) && $user['gid'] != 1) {
				if ($this->is_ajax) {
					$this->response('0402', [], $k);
				} else {
					$this->response('0402', [], $k, $url, 302);
				}
			}
		}
		// hook controller_userinfo_end.php
		return $this->token = $user;
	}


	/**
	 * @param string $tpl
	 * @param array  $_data
	 *
	 * @return string
	 * @throws \Exception
	 * 加载模板文件输出PHP执行结果
	 */
	public function View($_data = [], $_tpl = '')
	{
		// hook controller_view_start.php
		$route = $r = $this->route;
		unset($route[0]);
		empty($_tpl) AND $_tpl = implode('.', $route);
		$_tpl = (!empty($this->route[0]) ? $this->route[0] : 'Index') . '/View/' . $_tpl;

		$filename = !empty($_ENV['plugin_view_files'][$_tpl . '.html']) ? $_ENV['plugin_view_files'][$_tpl . '.html'] : __APPDIR__ . 'Controller/' . $_tpl . '.html';
		// hook controller_view_filename.php
		!is_file($filename) AND $this->response('0007', '', '模板不存在:' . $_tpl);
		$__PRE__ = implode('_', $route);
		$__DIR__ = $r[0];
		if (is_array($_data)) {
			extract($_data, EXTR_OVERWRITE);
		}
		if ($this->assign) {
			extract($this->assign, EXTR_OVERWRITE);
		}
		ob_start();
		include _include($filename);
		$data = ob_get_contents();
		ob_end_clean();
		// hook controller_view_end.php
		return $data;
	}

	/**
	 * @param string $tpl
	 *
	 * @throws \Exception
	 * 通过接口请求返回模板源文件
	 */
	public function Template($_tpl = '', $_data = [])
	{
		// hook controller_template_start.php
		$route = $r = $this->route;
		unset($route[0]);
		empty($_tpl) AND $_tpl = implode('.', $route);
		$_tpl = (isset($this->route[0]) ? $this->route[0] : 'Index') . '/View/' . $_tpl;

		$filename = !empty($_ENV['plugin_view_files'][$_tpl . '.html']) ? $_ENV['plugin_view_files'][$_tpl . '.html'] : __APPDIR__ . 'Controller/' . $_tpl . '.html';
		// hook controller_template_filename.php
		!is_file($filename) AND $this->response('0007', '', '模板不存在:' . $_tpl);
		$__PRE__ = implode('_', $route);
		$__DIR__ = $r[0];
		if (is_array($_data)) {
			extract($_data, EXTR_OVERWRITE);
		}
		if ($this->assign) {
			extract($this->assign, EXTR_OVERWRITE);
		}
		ob_start();
		include _include($filename);
		$data = ob_get_contents();
		ob_end_clean();
		// hook controller_template_end.php
		$this->response('0000', ['data' => ['tpl' => $data]]);
	}



	public function eazyLog($content, $pkId, $notice_param = [], $noticeUid = "")
	{
		$this->MsLog->add($this->route, $this->token["uid"], $content, $pkId, $this->request->get_client_ip(), $this->request->param(), $notice_param, $noticeUid);
	}


	public function loadData($needData = [])
	{
		if (empty($needData)) {
			return [];
		}
		$returnData = [];
		foreach ($needData as $index => $needDatum) {
			$returnData[$needDatum] = $this->request->param($needDatum, "");
		}
		return $returnData;
	}

	public function imgUrl($img)
	{
		$domain = ($this->request->_header["origin"]);
		return imgReturnLink($img, $domain);
	}

	public function returnTimeCond($timeStr)
	{
		$times = explode("~", $timeStr);
		$start = strtotime($times[0]);
		$end = strtotime($times[1]) + 86399;
		return [
			">=" => $start,
			"<=" => $end,
		];
	}
	// hook controller_end.php
}

?>