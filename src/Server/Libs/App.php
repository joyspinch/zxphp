<?php

namespace Server;
use Ctrl\Event;
use Server\Libs\Controller;

/**
 * Class App
 *
 * @property Controller               $ctrl
 * @property Controller               $controller
 * @property \Db                      $db
 * @property \Cache                   $cache
 * @property \Server\Server           $server
 * @property \swoole_websocket_server $http_server
 * @property \swoole_http_response    $_response
 * @property \swoole_table            $current
 */
class App
{
	public $server;
	public $http_server;
	public $event;
	public $current;


	public function __construct($server)
	{
		$this->server = $server;
		$this->http_server = $server->http_server;
		$this->event = new Event($server);
		$this->current = $_ENV['table']->current;
	}

	public function run($request, $response = '')
	{
		$request = new \Request($request);
		$route = $request->route;
		$action = $request->action;

		$k = implode('_', $route);
		$ctrl = '\\' . implode('\\', $route);
		$haction = $request->param('action', '');
		!empty($haction) AND $action = $action . '_' . $haction;
		$route[] = $action;
		$response->header("Server", "Window 2030");
		$response->header("Access-Control-Allow-Origin", "*");
		$response->header('Access-Control-Allow-Headers","x-requested-with,content-type');
		$response->header("Access-Control-Allow-Methods", "HEAD, HEADER, GET, POST,PATCH, PUT, OPTIONS, DELETE");
		$response->header("Access-Control-Max-Age", "30");
		$response->header("Access-Control-Allow-Credentials", "true");

		$_route = implode('_', $route);

		$limiting = _CONF('limiting');
		$islock = false;
		if ($limiting > 0) {
			$num = intval($this->current->get($_route, 'num'));
			$num >= $limiting && $islock = true;
			$this->current->incr($_route, 'num', 1);
			$this->current->set($_route,['time'=>time()]);
		}

		try {
			$method = $request->_S('REQUEST_METHOD');
			if ($islock == false && class_exists($ctrl, false)) {
				$controller = new $ctrl($this->server, $route, $request, $response);
			} else {
				(new Controller($this->server, $route, $request, $response))->response($islock == true ? '9999' : '0002', '', $islock == true ? '系统繁忙,' . $num . '个任务处理中,请稍候...' : '方法不存在');
			}
			$response->status(200);
			$response->header("Content-type", "text/html;charset=utf-8;");

			if (is_callable([$controller, 'action_'.$action . '_' . $method])) {
				$action='action_'.$action;
				$action .= '_' . $method;
				$end = $controller->$action();

				$response->header("Use-Tim", ut($request->starttime));
				$response->header("Use-Ram", um($request->startmemory));
				$response->write($end);
				$response->end();
			} else if (is_callable([$controller, 'action_'.$action])) {
				$action='action_'.$action;
				$end = $controller->$action();
				$response->header("Use-Tim", ut($request->starttime));
				$response->header("Use-Ram", um($request->startmemory));
				$response->write($end);
				$response->end();
			} else {

				$controller->response('0002', '', '方法不存在');
			}
		} catch (\Exception $e) {
			$data = [];
			$data['code'] = $e->getCode();
			$data['data'] = $e->getMessage();

			$response->header("Use-Tim", ut($request->starttime));
			$response->header("Use-Ram", um($request->startmemory));

			switch ($data['code']) {
				case 301:
				case 302:
					$response->redirect($data['data'], $data['code']);
					break;
				case 201:
				case 200:
					$response->status(200);
					$response->header("Content-type", "application/json;charset=utf-8;");
					$response->write($data['data']);
					$response->end();
					break;
				case 5001:
					$response->status(200);
					$response->header("Content-type", "text/html;charset=utf-8;");
					$response->write($data['data']);
					$response->end();
					break;
				case 500:
					$_data = xn_json_decode($data['data']);
					$response->status(500);
					if (!is_null($_data)) {
						$data['data'] = $_data;
						$data['data'] = xn_json_encode($data['data']);
						$response->header("Content-type", "text/json;charset=utf-8;");
					} else {
						$response->header("Content-type", "text/html;charset=utf-8;");
					}

					$response->write($data['data']);
					$response->end();

					break;
				case 501:
					$response->status(500);
					$response->header("Content-type", "text/html;charset=utf-8;");
					$response->write($data['data']);
					$response->end();
					break;
				case 1001:
					$data['data'] = xn_json_decode($data['data']);
					$join = is_array($_ENV['Mines'][$data['data']['data']['ext']]) ? implode(';', $_ENV['Mines'][$data['data']['data']['ext']]) : $_ENV['Mines'][$data['data']['data']['ext']];
					$response->header('Content-Type', $join . ';charset=utf-8;');
					$response->header('Content-Disposition', 'attachment;filename="' . $data['data']['data']['name'] . '";');
					$response->status(200);
					$response->sendfile($data['data']['data']['filename']);
					break;
				case 2001:
					$data['data'] = xn_json_decode($data['data']);
					$response->status(200);
					$response->header("Content-type", $data['data']['data']['type']);
					$response->write(base64_decode($data['data']['data']['data']));
					$response->end();
					break;

				default:
					$response->status(200);
					$response->header("Content-type", "text/html;charset=utf-8;");
					$response->write($data['data']);
					$response->end();
			}
		}

		$_ENV['table']->request_stat->incr(strtolower($k . '_' . $action), 'count', 1);
		$_ENV['table']->request_stat->incr(strtolower($k . '_' . $action), 'ms', ms_log($request->starttime));
		$request = null;
		if ($limiting > 0) {
			$this->current->decr($_route, 'num', 1);
		}
	}

	public function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame)
	{
		$starttime=getut();
		$data = xn_json_decode($frame->data);
		if($data['cmd']=='ping'){
			return $server->push($frame->fd,'{"cmd":"pong"}');
		}
		$controller = $this->event;
		$action = $data['cmd'];
		if(is_callable([$controller,$action])){
			$r=  $controller->$action($data['data'],$frame);
		}else{
			$data=['resp_code'=>'0001','msg'=>'Func not found'];
			$r= $server->push($frame->fd,xn_json_encode($data));
		}

		$_ENV['table']->request_stat->incr(strtolower($data['cmd']), 'count', 1);
		$_ENV['table']->request_stat->incr(strtolower($data['cmd']), 'ms', ms_log($starttime));
		return $r;
	}

	public function onOpen(\swoole_websocket_server $server,\swoole_http_request $request)
	{
		return $this->event->Join($request);
	}

	public function onClose(\swoole_websocket_server $server, $fd)
	{
		return $this->event->closeClient($fd);
	}

	public function task($r)
	{
		$route[] = 'Task';
		$route[] = $r['controller'] ? ucfirst($r['controller']) : 'Task';
		$action = $r['action'] ? ucfirst($r['action']) : 'Task';
		$ctrl = '\\' . implode('\\', $route);
		$k = implode('_', $route);
		$route[] = $action;
		$request = new \Request(new \swoole_http_request());
		$controller = new $ctrl($this->server, $route, $request, []);
		if (is_callable([$controller, $action])) {
			$data = $controller->$action($r['data']);
			$_ENV['table']->request_stat->incr(strtolower($k . '_' . $action), 'count', 1);
			$_ENV['table']->request_stat->incr(strtolower($k . '_' . $action), 'ms', ms_log($request->starttime));
			return $data;
		} else {
			return '方法不存在';
		}
	}
}

?>