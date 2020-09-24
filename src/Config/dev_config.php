<?php
return [
	'debug' => true,
	'db' => [
		'debug' => false,
		'database_type' => 'mysql',
		'database_name' => 'fanli',
		'server' => '127.0.0.1',//'47.104.200.246',
		'username' => 'root',
		'password' => 'root',
		'charset' => 'utf8',
		'prefix' => 'zx_',
	],
//    'db1' => [
//        'database_type' => 'mysql',
//        'database_name' => 'WebDB',
//        'server' => '127.0.0.1',
//        'username' => 'root',
//        'password' => 'root',
//        'charset' => 'utf8mb4',
//        'port' => 3306,
//        'prefix' => '',
//    ],
	'cache' => [
		'host' => '127.0.0.1',
		'port' => 6379,
		'select' => 3,
		'password' => '',
		'prefix' => ''
	],
	'url_suffix' => 'aspx|do|html',//do|html
	'app_v' => time(),
	'send_stat' => 8,
	'site_name' => '免税店',
	'session_id' => 'session_id',
	'auth_key'=>'MchxIeMKkFcqf6fkmRlMoPi0Qifl7OQV',
	'route_max' => 3,
	'limiting' => 1000,//单个请求并发锁
];
?>