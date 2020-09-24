<?php

namespace Server\Libs;

use Swoole\Timer;

/**
 * Class Controller
 *
 * @package Server\Libs
 * @property \Request              $request
 * @property \swoole_http_response $_response
 * @property \Server\Server        $server
 * @property \swoole_server        $http_server
 *
 * //IDE_LOAD_START
 *
 * //IDE_LOAD_END
 */
Class Controller
{

	public $server;
	public $not_closed = 0;
	public $session;

	/**
	 * User: zhixiang
	 *  Explain:
	 *  -
	 *
	 * Ctrl constructor.
	 *
	 * @param $conf
	 */
	public function __construct($server, $route, \Request $request, $response)
	{
		$this->server = $server;
		$this->http_server = $server->http_server;
		$this->is_ajax = strtolower($request->_S('X-REQUESTED-WITH')) == 'xmlhttprequest';
		$this->_method = $request->_S('REQUEST_METHOD');
		$this->token = [];
		$this->assign = [];
		$this->route = $route;
		$this->_route = implode('_', $route);
		$this->request = $request;
		$this->_response = $response;
	}


	public function auto_insert(Model $model, $data = [])
	{
		$rule = $model->install_rule_get();


	}

	public function auto_update(Model $model, $cond, $rule)
	{
		$rule = $model->update_rule_get();

	}

	public function auto_delete($model, $cond)
	{


	}

	/**
	 * @title  table_check
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param        $key
	 * @param string $field
	 * @param int    $time 多少毫秒后自动解锁
	 *                     2020/7/26 21:56
	 */
	public function table_check($key, $info = '操作过于频繁', $time = 60000)
	{
		$num = $_ENV['table']->tmp_lock->get($key, 'lock');
		$_time = getut();
		if (!empty($num) && $num >= $_time) {
			$this->response('0001', [], $info);
		}
		$_ENV['table']->tmp_lock->set($key, ['lock' => $_time + $time]);
	}

	/**
	 * @title  table_unlock
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 *
	 * @param     $table
	 * @param     $key
	 * @param int $sleep
	 * 2020/7/26 21:56
	 */
	public function table_unlock($key, $sleep = 1)
	{
		if ($sleep == 0) {
			$_ENV['table']->tmp_lock->del($key);
		} else {
			Timer::after($sleep, function () use ($key) {
				$_ENV['table']->tmp_lock->del($key);
			});
		}
	}

	/**
	 * User: zhixiang
	 *  Explain:
	 *  -
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($_ENV['_models'][$name])) {
			return $this->$name = $_ENV['_models'][$name];
		} else {
			throw new \Exception($name . 'Model禁止访问', '500');
		}
	}

	public function add_header($key, $value)
	{
		$this->_response->header($key, $value);
	}

	public function setcookie($name, $value, $expire = 3600, $path = '/')
	{
		return $this->_response->cookie(_CONF('cookie_tablepre') . $name, $value, time() + $expire, $path);
	}

	public function set_cookie($name, $value, $expire = 3600, $path = '/')
	{
		return $this->_response->cookie($name, $value, time() + $expire, $path);
	}


	public function response($code = '0000', $data = [], $msg = '', $url = '', $status = 200, $json = 0)
	{
		// hook common_response_before.php
		$json = $this->is_ajax ? $this->is_ajax : $json;

		if ($status == 301 || $status == 302) {
			$result = $url;
		} else {
			// hook common_response_code_before.php
			$_msg = $msg ? $msg : (isset($_ENV['ErrorCode'][$code]) ? $_ENV['ErrorCode'][$code] : '操作失败');
			if ($json == 1) {
				$result = [
					'resp_code' => $code,
					'msg' => $_msg,
				];

				// hook common_response_data_before.php
				is_array($data) AND $result = array_merge($result, $data);
				// hook common_response_data_json_before.php
				$result = xn_json_encode($result);
				// hook common_response_data_json_after.php
			} else {
				$error_file = _CONF('error_file', __CONDIR__ . 'Error.php');
				$result = !empty($_msg) ? ($msg ? (is_array($msg) ? $_msg . '(' . $msg[0] . ')' : $msg) : $_msg) : ($msg ? $msg : '操作成功');
				if (is_file($error_file)) {
					$status = 5001;
					if ($this->assign) {
						extract($this->assign, EXTR_OVERWRITE);
					}
					ob_start();
					include _include($error_file);
					$result = ob_get_contents();
					ob_end_clean();
				}
			}
		}

		// hook common_response_after.php
		throw new \Exception($result, $status);
	}


	public function json()
	{

	}

	public function redirect($url, $time = 0, $code = 302)
	{
		if ($time == 0) {
			$this->response('0000', $url, '', $url, $code, 1);
		} else {
			$html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport" /><title>跳转中...</title></head><body><div style="margin:12rem auto;text-align:center"><h1 style="font-size:28px;">请稍候... <span style="font-size:24px; color:#5FB878" id="time">' . $time . '</span> 或者<a href="' . $url . '">点我立即前往</a></h1></div></body></html>';
			$html .= '<script>setInterval(function () { var time = document.getElementById(\'time\').innerText;time = parseInt(time);time--;if (time >0) {document.getElementById(\'time\').innerText=time;} else { window.location = "' . $url . '";}}, 1000);</script>';
			return $html;
		}
	}

	//投递异步任务
	public function PostTask($action, $data, $_controller = 'Task')
	{

		return $this->http_server->task([
			'controller' => $_controller,
			'action' => $action,
			'data' => $data,
		]);
	}

//	public function SendMessage($data, $workerid = -1)
//	{
//		if ($workerid != -1) {
//			$workerid = is_array($workerid) ? $workerid : [$workerid];
//			foreach ($workerid as $worker_id) {
//				$this->http_server->sendMessage($data, $worker_id);
//			}
//		} else {
//			for ($worker_id = 0; $worker_id < $this->http_server->setting['worker_num'] + $this->http_server->setting['task_worker_num']; $worker_id++) {
//				$this->http_server->sendMessage($data, $worker_id);
//			}
//		}
//	}

	/**
	 * @param $value
	 * @param $text
	 *
	 * @throws \Exception
	 * 检测变量是否为空
	 */
	public function CheckEmpty($value, $text, $allowZero = true)
	{
		// hook controller_checkempty_start.php
		$i = 0;
		foreach ($value as $k => $val) {
			if (empty($val)) {
				if (is_numeric($val) && $allowZero) {
					$i++;
					continue;

				}
				$this->response('0003', [], $text[$i] . '不能为空');
			}
			$i++;
		}
		// hook controller_checkempty_end.php
	}

}

?>