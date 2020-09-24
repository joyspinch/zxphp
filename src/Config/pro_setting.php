<?php
return [
	'open_http_protocol' => true,
	'open_websocket_protocol' => true,
	'open_mqtt_protocol' => false,
	'worker_num' => 4,
	'task_worker_num' => 4,
	'max_coroutine' => 30000,
	'heartbeat_idle_time'      => 180, //表示一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
	'heartbeat_check_interval' => 60,  //表示每60秒遍历一次
	'log_level' => SWOOLE_LOG_ERROR,
	'trace_flags' => SWOOLE_TRACE_ALL,
	'port' => 8003,
	'hook_flags' => SWOOLE_HOOK_UNIX | SWOOLE_HOOK_CURL |SWOOLE_HOOK_SLEEP,
	//'user' => 'www',
	'enable_coroutine' => true,
	'task_enable_coroutine' => true,

	'enable_static_handler' => true,
	'upload_tmp_dir' => __WEBDIR__,
	'document_root' => __WEBDIR__,
	'buffer_output_size' => 64 * 1024 * 1024, //必须为数字
	'package_max_length' => 64 * 1024 * 1024,//最大64m文件

	//        'tcp_fastopen' => true,
	//        'reload_async' => true,
];

?>