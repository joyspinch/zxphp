<?php
return [
	// +----------------------------------------------------------------------
	// | 内存表设置
	// +----------------------------------------------------------------------
	//'request_stat' => ['num'=>2048,'fields'=>['ms'=>swoole_table::TYPE_INT,'count'=>swoole_table::TYPE_INT]], //此table 内置
	//'workers_stat' => ['num'=>2048,'fields'=>['ms'=>swoole_table::TYPE_INT,'count'=>swoole_table::TYPE_INT]], //此table 内置
	'views' => ['num' => 131072, 'fields' => ['views' => [\swoole_table::TYPE_INT, 4],]],
	'current' => ['num' => 131072, 'fields' => ['num' => [\swoole_table::TYPE_INT, 4],'time' => [\swoole_table::TYPE_INT, 4],]],
	'tmp_lock' => ['num' => 131072, 'fields' => ['lock' => [\swoole_table::TYPE_INT, 4],]],
	//以上内置默认table
	//以下websocket使用，open_websocket_protocol = true 时才会创建
	'fd_uid' => ['num' => 1048576, 'fields' => ['uid' => [\swoole_table::TYPE_STRING, 12],]],
	'uid_fd' => ['num' => 131072, 'fields' => ['fd' => [\swoole_table::TYPE_STRING, 880],]],
	'uid_gid' => ['num' => 131072, 'fields' => ['gid' => [\swoole_table::TYPE_STRING, 880],]],
	'gid_uid' => ['num' => 16384, 'fields' => ['uid' => [\swoole_table::TYPE_STRING, 5500],]],
	//以上websocket使用


	'player'=>['num'=>16384,'fields' => ['hp' => [\swoole_table::TYPE_INT, 4],'mp' => [\swoole_table::TYPE_INT, 4],]]

];
?>