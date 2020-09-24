<?php
/**
 * HTTP请求封装
 */

/**
 * Class Controller
 *
 * @package Server\Libs
 * @property \swoole_http_request $req
 */
class Request
{

	/** @var object $sw_request Swoole request object */
	private $request;
	private $req;

	/** @var string $module Module */
	public $module = null;

	/** @var string $action Action */
	public $action = null;

	public $time = 0;
	public $starttime = 0;
	public $startmemory = 0;

	/** @var array Same as swoole */
	public $_get;
	public $_post;

	public $server;
	public $_header;
	public $_cookie;
	public $files;
	public $route;

	public function __construct(swoole_http_request $req)
	{

		$this->time = time();
		$this->starttime = getut();
		$this->startmemory = $_ENV['startmemory'];
		$this->req = $req;
		$this->_get = (array)$req->get;
		$this->_post = (array)$req->post;
		$this->server = array_change_key_case(array_merge((array)$req->header, (array)$req->server), CASE_UPPER);

		!empty($this->server['X-CLIENT-SCHEME']) && $this->server['SCHEME']=$this->server['X-CLIENT-SCHEME'];
		!empty($this->server['ALI-CDN-REAL-IP']) && $this->server['X-REAL-IP']=$this->server['ALI-CDN-REAL-IP'];
		!empty($this->server['ACCESS-CONTROL-REQUEST-METHOD']) && $this->server['REQUEST_METHOD']=$this->server['ACCESS-CONTROL-REQUEST-METHOD'];
		!empty($this->server['ACCESS-CONTROL-REQUEST-HEADERS']) && $this->server['X-REQUESTED-WITH']= str_replace('x-requested-with','',$this->server['ACCESS-CONTROL-REQUEST-HEADERS'])!=$this->server['ACCESS-CONTROL-REQUEST-HEADERS']?'xmlhttprequest':$this->server['ACCESS-CONTROL-REQUEST-HEADERS'];
		$this->server['HTTP_HOST'] = !empty($this->server['SCHEME']) ? $this->server['SCHEME'] . '://' . $this->server['HOST'] : 'http://' . $this->server['HOST'];
		$this->server['WS_HOST'] = !empty($this->server['SCHEME']) ? str_replace('http','ws',$this->server['SCHEME']).'://' . $this->server['HOST'] : 'ws://' . $this->server['HOST'];
		$this->_header = (array)$req->header;
		$this->_cookie = (array)$req->cookie;
		$this->files = (array)$req->files;

		$request_url = $req->server["request_uri"];
		$request_url = ltrim(str_replace('/?', '/', $request_url), '/');

		$routeInfo = $_ENV['dispatcher']->dispatch($req->server['request_method'], $request_url);

		if ($routeInfo[0] == FastRoute\Dispatcher::FOUND) {
			$route = explode('/', $routeInfo[1]); // 获得处理函数
			$arr = $routeInfo[2];
		} else {
			$request = explode('?', $request_url);
			$route = explode('/', $request[0]);
			$arr = [];
			if (!empty($request[1])) {
				parse_str($request[1], $arr);
			}
		}
		$this->request = array_merge($this->_get, $this->_post, $this->_cookie, $route, $arr);


		$sid_key = _CONF('session_id');
		$tablepre = _CONF('cookie_tablepre');
		empty($sid_key) AND $sid_key = 'session_id';
		if (!empty($req->header['access_token'])) {
			$this->_cookie[$tablepre . $sid_key] = $req->header['access_token'];
			$this->server['X-REQUESTED-WITH'] = 'xmlhttprequest';
		}elseif (!empty($this->request['access_token'])) {
			$this->_cookie[$tablepre . $sid_key] = $this->request['access_token'];
			$this->server['X-REQUESTED-WITH'] = 'xmlhttprequest';
		}

		$i = max(2, count($route));
		$max = $_ENV['conf']['route_max'] ? $_ENV['conf']['route_max'] : 3;
		$last = $i > $max ? min($i, $max) : min($i, $max - 1);
		$action = !empty($route[$last]) ? ucfirst($this->param($last)) : 'Index';
		$_route = [];
		for ($ii = 0; $ii < $last; $ii++) {
			$_route[] = !empty($route[$ii]) ? ucfirst($this->param($ii)) : 'Index';
		}

		$this->route = $_route;
		$this->action = $action;


	}

	/**
	 * Get original post body
	 *
	 * @access public
	 * @return string
	 */
	public function rawContent()
	{
		return $this->req->rawContent();
	}


	public function param($key = '', $defval = '', $safe = true)
	{
		if (empty($key) && $key !== 0) {
			$val = $this->request;
		} else {
			if (!isset($this->request[$key]) || ($key === 0 && empty($this->request[$key]))) {
				if (is_array($defval)) {
					return [];
				} else {
					return $defval;
				}
			}
			$val = $this->request[$key];
			$val = $this->param_force($val, $defval, $safe);
		}

		return $val;
	}

	public function post($key = '', $defval = '', $safe = true)
	{
		if (empty($key) && $key !== 0) {
			$val = $this->_post;
		} else {
			if (!isset($this->_post[$key]) || ($key === 0 && empty($this->_post[$key]))) {
				if (is_array($defval)) {
					return [];
				} else {
					return trim($defval);
				}
			}
			$val = $this->_post[$key];
			$val = $this->param_force($val, $defval, $safe);
		}
		return $val;
	}

	public function get($key = '', $defval = '', $safe = true)
	{
		if (empty($key) && $key !== 0) {
			$val = $this->_get;
		} else {
			if (!isset($this->_get[$key]) || ($key === 0 && empty($this->_get[$key]))) {
				if (is_array($defval)) {
					return [];
				} else {
					return trim($defval);
				}
			}
			$val = $this->_get[$key];
			$val = $this->param_force($val, $defval, $safe);
		}
		return $val;
	}


	public function cookie($key = '', $defval = '', $safe = true)
	{
		if (empty($key) && $key !== 0) {
			$val = $this->_cookie;
		} else {
			if (!isset($this->_cookie[$key]) || ($key === 0 && empty($this->_cookie[$key]))) {
				if (is_array($defval)) {
					return [];
				} else {
					return trim($defval);
				}
			}
			$val = $this->_cookie[$key];
			$val = $this->param_force($val, $defval, $safe);
		}
		return $val;
	}

	public function _S($key, $defval = '', $safe = true)
	{
		if (!isset($this->server[$key]) || ($key === 0 && empty($this->server[$key]))) {
			if (is_array($defval)) {
				return [];
			} else {
				return trim($defval);
			}
		}
		$val = $this->server[$key];
		$val = $this->param_force($val, $defval, $safe);
		return $val;
	}


	/*
	仅支持一维数组的类型强制转换。
	param_force($val);
	param_force($val, '');
	param_force($val, 0);
	param_force($arr, array());
	param_force($arr, array(''));
	param_force($arr, array(0));
	*/
	public function param_force($val, $defval, $safe = true)
	{
		if (is_array($defval)) {
			$defval = empty($defval) ? '' : $defval[0]; // 数组的第一个元素，如果没有则为空字符串
			if (is_array($val)) {
				foreach ($val as &$v) {
					if (is_array($v)) {
						$v = $v;//trim($defval);//不转换了
					} else {
						if (is_string($defval)) {
							$v = trim($v);
							$safe AND !__GCP__ && $v = addslashes($v);
							!$safe AND __GCP__ && $v = stripslashes($v);
							$safe AND $v = htmlspecialchars($v);
						} else {
							$v = intval($v);
						}
					}
				}
			} else {
				return [];
			}
		} else {
			if (is_array($val)) {
				$val = trim($defval);
			} else {
				if (is_string($defval)) {
					$val = trim($val);
					$safe AND !__GCP__ && $val = addslashes($val);
					!$safe AND __GCP__ && $val = stripslashes($val);
					$safe AND $val = htmlspecialchars($val);

				} else {
					$val = intval($val);
				}
			}
		}

		return $val;
	}


	/**
	 * Finish request, release resources
	 *
	 * @access public
	 */
	public function __destruct()
	{
		$this->get = null;
		$this->post = null;
		$this->server = null;
		$this->header = null;
		$this->cookie = null;
		$this->files = null;
		$this->req = null;
		$this->request = null;
		$this->route = null;
	}

	/**
	 * 检测是否使用手机访问
	 *
	 * @access public
	 * @return bool
	 */
	public function isMobile()
	{
		if ($this->_S('VIA') && stristr($this->_S('VIA'), "wap")) {
			return true;
		} else if ($this->_S('ACCEPT') && strpos(strtoupper($this->_S('ACCEPT')), "VND.WAP.WML")) {
			return true;
		} else if ($this->_S('X-WAP-PROFILE') || $this->_S('PROFILE')) {
			return true;
		} else if ($this->_S('USER-AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->_S('USER-AGENT'))) {
			return true;
		}
		return false;
	}

	public function get_client_ip($type = 0)
	{
		$type = $type ? 1 : 0;
		$ip = $this->_S('REMOTE-ADDR');
		empty($ip) && $ip = $this->_S('REMOTE_ADDR');
		$newIp = $this->_S('X-FORWARDED-FOR');
		$rnewIp = $this->_S('X-REAL-IP');
		if (!empty($rnewIp)) {
			$rnewIp = explode(",", $rnewIp);
			$ip = $rnewIp[0];
		}else if (!empty($newIp)) {
			$newIp = explode(",", $newIp);
			$ip = $newIp[0];
		}
		$long = sprintf("%u", ip2long($ip));
		$ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
		return $ip[$type];
	}
}
