<?php

namespace Server;

if (extension_loaded("swoole")) {
	if (version_compare(SWOOLE_VERSION, '4.4.4', '>=') == false) {
		exit("swoole扩展版本必须大于等于4.4.3\n");
	}
} else {
	exit("必须安装swoole扩展\n");
}

if (version_compare(PHP_VERSION, '7.0.0', '>=') == false) {
	exit("PHP版本必须大于等于7.0.0\n");
}

/**
 * Class Server
 *
 * @package Server
 * @property \Request       $request
 * @property \swoole_server $http_server
 * @property App            $app
 */
class Server
{
	const VERSION = '1.1.9';
	public $name = "ZxPHP";
	private $_startFile = '';
	public $pidFile = '';
	private $logFile = '';
	private $set = ['daemonize' => false];
	public $http_server;
	private $app;
	private $ws;
	private $start_file;

	public function __construct($ip = '0.0.0.0', $set = [])
	{
		$this->set['log_file'] = $this->logFile;
		if (empty($set)) {
			$set["enable_reuse_port"] = true;
		}
		//$set['log_rotation']=SWOOLE_LOG_ROTATION_DAILY;
		$this->set = array_merge($this->set, $set);
		error_reporting(E_ERROR);

		$this->init();
		$this->parseCommand();
		if ($set['open_websocket_protocol'] == true) {
			$this->http_server = new \swoole_websocket_server($ip, $set['port']);
		} else if ($set['open_http_protocol'] == true) {
			$this->http_server = new \swoole_http_server($ip, $set['port']);
		} else {
			$this->http_server = new \swoole_server($ip, $set['port']);
		}

		$this->http_server->set($this->set);
		$this->http_server->on('start', [$this, 'onMasterStart']);
		$this->http_server->on('managerstop', [$this, 'onManagerStop']);
		$this->http_server->on('managerstart', [$this, 'onManagerStart']);
		$this->http_server->on('workerstart', [$this, 'onWorkerStart']);
		$this->http_server->on('workererror', [$this, 'onWorkerError']);
		$this->http_server->on('workerexit', [$this, 'onWorkerExit']);
		$this->http_server->on('request', [$this, 'onRequest']);

		if ($this->set['open_websocket_protocol'] == true) {
			$this->http_server->on('close', [$this, 'onClose']);
			$this->http_server->on('open', [$this, 'onOpen']);
			$this->http_server->on('message', [$this, 'onMessage']);
		}
		$this->http_server->on('task', [$this, 'onTask']);
		$this->http_server->on('finish', [$this, 'onFinish']);
		//$this->http_server->on('pipemessage', [$this, 'onPipeMessage']);
		define('__VERSION__', self::VERSION);
		define('__NAME__', $this->name);
		define('__WNUM__', $set['worker_num']);
		define('__TNUM__', $set['task_worker_num']);
		define('PLUGIN_OFFICIAL_URL', 'http://plugin.zxphp.top/');
	}


	public function onRequest($request, $response)
	{
		$this->error($response);
		try {
			$this->app->run($request, $response);
		} catch (\Exception $error) {
			$response->write($error->getMessage());
			$response->end();
		}
	}

	public function onTask($server, $task)
	{
		if (is_file(__COMDIR__ . 'onTask.php')) {
			include _include(__COMDIR__ . 'onTask.php');
		} else {
			$ret = $this->app->task($task->data);
			//$ret = $this->app->task($task->data);
			$task->finish($ret ? $task->data['controller'] . '_' . $task->data['action'] . ' ' . $ret : '');
		}
	}

	public function onFinish($serv, $task_id, $response)
	{
		!empty($response) && xn_log(xn_json_encode($response), 'task_finish');
	}


	public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
	{
		$this->app->onMessage($server, $frame);
	}

	public function onClose(\swoole_websocket_server $server, $fd)
	{
		$this->app->onClose($server, $fd);
	}

	public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
	{
		$this->app->onOpen($server, $request);
		//$this->app->onOpen($server, $request);
	}


	public function Table()
	{

		$workers = new \swoole_table(__WNUM__ + __TNUM__ + 2);
		$workers->column('request', \swoole_table::TYPE_INT);
		$workers->column('last_request', \swoole_table::TYPE_INT);
		$workers->column('memory', \swoole_table::TYPE_INT);
		$workers->create();
		$_ENV['table']->workers_stat = $workers;

		$workers = new \swoole_table(131072);
		$workers->column('ms', \swoole_table::TYPE_INT);
		$workers->column('count', \swoole_table::TYPE_INT);
		$workers->create();
		$_ENV['table']->request_stat = $workers;

		$socket_arr = ['uid_fd', 'fd_uid', 'uid_gid', 'gid_uid'];
		$tables = include __CONDIR__ . 'Tables.php';

		foreach ($tables as $k => $table) {
			if ($this->set['open_websocket_protocol'] == false && in_array($k, $socket_arr)) {
				continue;
			}
			$workers = new \swoole_table($table['num']);
			foreach ($table['fields'] as $_k => $v) {
				if (!empty($v[1])) {
					$workers->column($_k, $v[0], $v[1]);
				} else {
					$workers->column($_k, $v[0]);
				}
			}
			$workers->create();
			$_ENV['table']->$k = $workers;
			//echo bcdiv($workers->memorySize, 1024 * 1024, 4), 'M ';
		}
	}

	protected function error($response)
	{
		set_error_handler(function ($code, $message, $file, $line) use ($response) {
			$message = sprintf("C：%s \nM：%s \nF：%s \nL：%s\n", $code, $message, $file, $line);
			self::log('ERROR1 ' . $message, 'php_error');
			if (!_CONF('debug')) {
				return false;
			}
			$response->end($message);
		}, E_ERROR);

		set_exception_handler(function (\Throwable $error) use ($response) {
			$message = sprintf("C：%s \nM：%s \nF：%s \nL：%s\n", $error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine());
			self::log('ERROR2 ' . $message, 'php_error');
			$response->end($message);
		});

		register_shutdown_function(function () use ($response) {
			$error = error_get_last();
			if (empty($error['type'])) {
				return false;
			}
			ob_start();
			include _include(__ROTDIR__ . 'ErrorView/500.php');
			$data = ob_get_contents();
			ob_clean();;
			$response->end($data);

			$message = '';

			switch ($error['type']) {
				case E_WARNING:
				case E_NOTICE:
					return true;
				case E_ERROR:
					$message = ' E_ERROR ';
					break;
				case E_PARSE:
					$message = ' E_PARSE ';
					break;
				case E_CORE_ERROR:
					$message = ' E_CORE_ERROR ';
					break;
				case E_COMPILE_ERROR:
					$message = ' E_COMPILE_ERROR ';
					break;
				default:
			}
			$type = strtolower(trim($message));
			$message .= sprintf("\nM：%s \nF：%s \nL：%s\n", $error['message'], $error['file'], $error['line']);
			self::log($message, 'php_' . $type . '_error');
			return false;
		});
	}


	protected function init()
	{
		ini_set('zend.enable_gc', 0);
		!defined('__LV__') AND define('__LV__', 'dev');
		!defined('__GCP__') AND define('__GCP__', get_magic_quotes_gpc());
		!defined('IS_CLI') AND define('IS_CLI', true);
		!defined('DS') AND define('DS', DIRECTORY_SEPARATOR);
		!defined('__ROTDIR__') AND define('__ROTDIR__', dirname(__DIR__) . 'Server.php/');
		!defined('__CONDIR__') AND define('__CONDIR__', __ROTDIR__ . 'Config/');
		!defined('__SERDIR__') AND define('__SERDIR__', __ROTDIR__ . 'Server/');
		!defined('__COMDIR__') AND define('__COMDIR__', __ROTDIR__ . 'Common/');

		!defined('__VENDIR__') AND define('__VENDIR__', __ROTDIR__ . 'vendor/');
		!defined('__APPDIR__') AND define('__APPDIR__', __ROTDIR__ . 'App/');
		!defined('__PLUDIR__') AND define('__PLUDIR__', __APPDIR__ . 'Plugin/');
		!defined('__PIDDIR__') AND define('__PIDDIR__', __ROTDIR__ . 'Pid/');
		!defined('__WEBDIR__') AND define('__WEBDIR__', __ROTDIR__ . 'Web/');
		!defined('__UPFDIR__') AND define('__UPFDIR__', __WEBDIR__ . 'uploads/');
		!defined('__PUBDIR__') AND define('__PUBDIR__', __APPDIR__ . 'Public/');
		!defined('__UPPATH__') AND define('__UPPATH__', '../../uploads/');
		!defined('__CAHDIR__') AND define('__CAHDIR__', __ROTDIR__ . 'Cache/');
		!defined('__IDEDIR__') AND define('__IDEDIR__', __CAHDIR__ . 'Ide/');
		!defined('__LOGDIR__') AND define('__LOGDIR__', __CAHDIR__ . 'Log/');
		!defined('__STADIR__') AND define('__STADIR__', __CAHDIR__ . 'Stat/');//统计
		!defined('__RELOAD__') AND define('__RELOAD__', 0);//开发环境 毫秒重载时间 >0 执行 30000
		!defined('__TMPDIR__') AND define('__TMPDIR__', __ROTDIR__ . 'Tmp/');//Linux下可以直接丢进 内存 /dev/shm
		!is_dir(__PIDDIR__) AND mkdir(__PIDDIR__, 0777, 1);
		!is_dir(__STADIR__) AND mkdir(__STADIR__, 0777, 1);
		!is_dir(__COLDIR__) AND mkdir(__COLDIR__, 0777, 1);
		!is_dir(__UPFDIR__) AND mkdir(__UPFDIR__, 0777, 1);
		!is_dir(__TMPDIR__) AND mkdir(__TMPDIR__, 0777, 1);
		!is_dir(__IDEDIR__) AND mkdir(__IDEDIR__, 0777, 1);

		$backtrace = debug_backtrace();
		$this->_startFile = $backtrace[0]['file'];
		$this->pidFile = __PIDDIR__ . str_replace('/', '_', $this->_startFile) . ".pid";
		$this->set['log_file'] = $this->logFile = __CAHDIR__ . 'Server/Server_' . date('Ymd') . '.log';
		!is_dir(__CAHDIR__ . 'Server/') AND mkdir(__CAHDIR__ . 'Server/', 0777, 1);
		//加载框架文件
		$Func = glob(__SERDIR__ . 'Func/*.php');
		foreach ($Func as $_file) {
			include_once $_file;
		}

		$Class = glob(__SERDIR__ . 'Class/*.php');
		foreach ($Class as $_file) {
			include_once $_file;
		}

		$libs = glob(__SERDIR__ . 'Libs/*.php');
		foreach ($libs as $_file) {
			include_once $_file;
		}
		include_once __VENDIR__ . 'autoload.php';
	}

	protected function parseCommand()
	{
		global $argv;
		$this->start_file = $start_file = $argv[0];
		if (!isset($argv[1])) {
			exit("Usage: php yourfile.php {start|stop|restart|reload}\n");
		}
		$command1 = trim($argv[1]);
		$command2 = isset($argv[2]) ? $argv[2] : '';
		$mode = '';
		if ($command1 === 'start') {
			if ($command2 === '-d') {
				$mode = 'in DAEMON mode';
			} else {
				$mode = 'in DEBUG mode';
			}
		}

		// Get master process PID.
		$master_pid = is_file($this->pidFile) ? file_get_contents($this->pidFile) : 0;
		$master_is_alive = $master_pid && posix_kill($master_pid, 0) && posix_getpid() != $master_pid;

		if ($master_is_alive) {
			if ($command1 === 'start') {
				$this->log("Server [$start_file] already running");
				exit;
			}
		} else if ($command1 !== 'start' && $command1 !== 'restart') {
			$this->log("Server [$start_file] not run");
			exit;
		}

		switch ($command1) {
			case 'start':
				rmdir_tmp();
				$this->Table();
				if ($command2 === '-d') {
					$this->set['daemonize'] = true;
				}
				$this->log("Server [$start_file] $command1 $mode");
				break;
			case 'restart':
			case 'stop':
				$this->log("Server [$start_file] is stoping ...");
				$master_pid && posix_kill($master_pid, SIGTERM);
				$timeout = 5;
				$start_time = time();
				// Check master process is still alive?
				while (1) {
					$master_is_alive = $master_pid && posix_kill($master_pid, 0);
					if ($master_is_alive) {
						// Timeout?
						if (time() - $start_time >= $timeout) {
							$this->log("Server [$start_file] stop fail");
							exit;
						}
						// Waiting amoment.
						usleep(10000);
						continue;
					}
					// Stop success.
					$this->log("Server [$start_file] stop success");
					if ($command1 === 'stop') {
						exit(0);
					}
					rmdir_tmp();
					$this->set['daemonize'] = true;
					break;
				}
				break;
			case 'reload':
				rmdir_tmp();
				if ($command2 === '-g') {
					$sig = SIGQUIT;
				} else {
					$sig = SIGUSR1;
				}
				posix_kill($master_pid, $sig);
				self::log("Server [$start_file] reload success");
				exit;
			default :
				exit("Usage: php yourfile.php {start|stop|restart|reload}\n");
		}
	}

	public function onMasterStart($serv)
	{
		if (false === @file_put_contents($this->pidFile, $serv->master_pid)) {
			throw new \Exception('can not save pid to ' . $this->pidFile);
		}

		PHP_OS != 'Darwin' && swoole_set_process_name("Server: master process " . $this->name . " start_file=" . $this->_startFile);
		//仅供支持IDE从模型获取
		$include_model_files = glob(__APPDIR__ . 'Model/*Model.php');
		foreach ($include_model_files as $model_files) {
			$name = str_replace([__APPDIR__ . 'Model/', 'Model.php'], '', $model_files);
			$str[] = " * @property \Model\\{$name}Model \$$name";
		}
		$include_model_files = glob(__APPDIR__ . 'Model/*/*Model.php');
		foreach ($include_model_files as $model_files) {
			$name = str_replace([dirname($model_files).'/', 'Model.php'], '', $model_files);
			$str[] = " * @property \Model\\{$name}Model \$$name";
		}

		//从开启的插件中获取model
		$model_name = plugin_get_model();
		foreach ($model_name as $name) {
			$str[] = " * @property \Model\\{$name}Model \${$name}";
		}

		$str = implode("\n", $str);
		IDE_include(__ROTDIR__ . 'Server/Libs/Controller.php', $str);
		IDE_include(__ROTDIR__ . 'Server/Libs/Model.php', $str);
		IDE_include(__ROTDIR__ . 'Server/Libs/Event.php', $str);
		//以上为IDE提供支持
		plugin_clear_tmp_dir();
		$_ENV['conf'] = include __CONDIR__ . __LV__ . '_config.php';
		echo "-------------------------- ZxPHP --------------------------------\r\n";
		echo 'ZxPHP:' . self::VERSION . '  Swoole:' . SWOOLE_VERSION . '  PHP:' . PHP_VERSION . "  OPcache:" . (function_exists('opcache_reset') ? 'Open' : 'Close') . "  Run:" . __LV__ . "\r\n";
		echo "-------------------------- Table --------------------------------\r\n";
		$memorySize = 0;
		foreach ($_ENV['table'] as $name => $table) {
			$memorySize = bcadd($memorySize, $table->memorySize);
			echo 'Name:' . str_pad($name, 20) . ' Size:' . str_pad($table->size, 10) . '  MemorySize:' . str_pad(bcdiv($table->memorySize, 1024 * 1024, 2) . "M", 12) . "\r\n";
		}

		echo "MemorySize:" . str_pad(bcdiv($memorySize, 1024 * 1024, 2) . 'M', 12) . "  Use:\$_ENV['table']->Name->func()\r\n";
		echo "-----------------------------------------------------------------\r\n";
		echo "Master pid:" . $serv->master_pid . '     Start at:' . date('Y-m-d H:i:s') . "\r\n";
		echo "Worker Num:{$this->set['worker_num']}  Task Num:{$this->set['task_worker_num']}\r\n";
		echo 'Worker Co:' . ($this->set['enable_coroutine'] ? 'true' : 'false') . '  Task Co:' . ($this->set['task_enable_coroutine'] ? 'true' : 'false') . "  Max Co:{$this->set['max_coroutine']}\r\n";
		echo "Protocol HTTP:" . ($this->set['open_http_protocol'] ? 'true' : 'false') . "  WEBSOCKET:" . ($this->set['open_websocket_protocol'] ? 'true' : 'false') . "  MQTT:" . ($this->set['open_mqtt_protocol'] ? 'true' : 'false') . "   Listen Port:{$this->set['port']}\r\n";
		echo "-------------------------- Worker --------------------------------\r\n";

	}

	public function onManagerStop($serv)
	{
		if (is_file(__COMDIR__ . 'onManagerStop.php')) {
			include _include(__COMDIR__ . 'onManagerStop.php');
		} else {
			unlink($this->pidFile);
		}
	}

	public function onManagerStart($serv)
	{
		if (is_file(__COMDIR__ . 'onManagerStart.php')) {
			include _include(__COMDIR__ . 'onManagerStart.php');
		} else {
			PHP_OS != 'Darwin' && swoole_set_process_name("Server: manage process " . $this->name . " start_file=" . $this->_startFile);
		}
	}

	public function onWorkerStart(\swoole_server $serv, $worker_id)
	{
		if (is_file(__COMDIR__ . 'onWorkerStart.php')) {
			include _include(__COMDIR__ . 'onWorkerStart.php');
		} else {
			//PHP_OS != 'Darwin' && swoole_set_process_name("Server: worker process " . $this->name . " start_file=" . $this->_startFile);
			if ($worker_id >= $this->set['worker_num']) {
				PHP_OS != 'Darwin' && swoole_set_process_name("{$this->name} php {$this->start_file} task worker");
			} else {
				PHP_OS != 'Darwin' && swoole_set_process_name("{$this->name} php {$this->start_file} event worker");
			}

			if (function_exists('opcache_reset')) {
				opcache_reset();
			}
//			if (function_exists('apc_clear_cache')) {
//				apc_clear_cache();
//			}
			$_ENV['startmemory'] = getum();
			$_ENV['conf'] = include __CONDIR__ . __LV__ . '_config.php';
			$_ENV['conf']['url_suffix'] = explode('|', $_ENV['conf']['url_suffix']);
			$_ENV['cache_class'] = $_ENV['conf']['cache'] ? new \Cache($_ENV['conf']['cache']) : NULL;
			$_ENV['db_class']['db'] = $_ENV['conf']['db'] ? new \Db($_ENV['conf']['db']) : NULL;
			$_ENV['Ip2Region'] = new \Ip2Region();
			$_ENV['Ip2Region']->memorySearch('127.0.0.1');
			for ($i = 1; $i < 5; $i++) {
				if (!empty($_ENV['conf']['db' . $i])) {
					$_ENV['db_class']['db' . $i] = new \Db($_ENV['conf']['db' . $i]);
				}
			}


			$_ENV['plugin_srcfiles'] = [];
			$_ENV['plugin_paths'] = [];
			$_ENV['plugins'] = [];// 跟官方插件合并
			$_ENV['official_plugins'] = [];// 官方插件列表
			$_ENV['g_include_slot_kv'] = [];// 官方插件列表

			plugin_init();//插件初始化
			plugin_get_hook();//获取启用的钩子
			plugin_get_model();
			plugin_get_controller();
			require_once _include(__COMDIR__ . 'Common.php');

			ConfigReload();

			if (in_array(intval($_ENV['conf']['upload_type']), [2, 3], 1)) {
				$_ENV['conf']['upload_domain'] = str_replace(['http://', 'https://'], '', _CONF('upload_domain', ''));
				!empty($_ENV['conf']['upload_domain']) && $_ENV['conf']['upload_domain'] = '//' . $_ENV['conf']['upload_domain'];
			} else {
				$_ENV['conf']['upload_domain'] = '';
			}

			IncludeFiles($this);
			require_once _include(__COMDIR__ . 'Tick.php');
			$this->app = new App($this);
		}
		echo "Worker ID:" . str_pad($worker_id, 5) . ' Pid:' . str_pad($serv->worker_pid, 8) . ' Task:' . ($serv->taskworker ? 'true' : 'false') . "\r\n";
	}

	public function onWorkerError(\swoole_server $serv, $worker_id, $worker_pid, $exit_code)
	{
		$this->log("worker_pid: " . $worker_pid . " exit_code:" . $exit_code . ' ' . $serv->getLastError());
		$last = $_ENV['table']->workers_stat->get($worker_id);
		$d = $serv->stats();
		$istask = $serv->taskworker ? 'M' : 'W';
		$workerIdk = $worker_id . $istask;
		if ($last != false) {
			empty($d['worker_request_count']) AND $d['worker_request_count'] = 0;
			$_ENV['table']->workers_stat->set($workerIdk, ['request' => $last['request'] + ($d['worker_request_count'] - $last['last_request']), 'last_request' => 0, 'memory' => memory_get_usage()]);
		} else {
			$_ENV['table']->workers_stat->set($workerIdk, ['request' => 0, 'last_request' => 0, 'memory' => memory_get_usage()]);
		}
	}


	public function onWorkerExit($server, $worker_id)
	{
		echo 'WorkerExit ' . $worker_id . "\r\n";
	}


	public function onPipeMessage($serv, $src_worker_id, $data)
	{
		echo "#{$serv->worker_id} message from #$src_worker_id: $data\n";
	}

	public function log($msg, $file = 'php_log')
	{
		xn_log($msg, $file);
		if (empty($this->set['daemonize'])) {
			echo date('Y-m-d H:i:s') . " " . $msg . "\n";
		}
	}

	public function run()
	{
		$this->http_server->start();
	}
}

?>