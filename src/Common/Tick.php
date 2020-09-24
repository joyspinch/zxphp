<?php
/**
 * Created by PhpStorm.
 * User: 智祥
 * Date: 2019-01-30
 * Time: 15:50
 */
// hook tick_worker_start.php
$http_server = $this->http_server;
$workerId = $serv->worker_id;
$istask = $serv->taskworker ? 'M' : 'W';
$workerIdk = $workerId . $istask;
$last = $_ENV['table']->workers_stat->get($workerIdk);
if ($last != false) {
	$_ENV['table']->workers_stat->set($workerIdk, ['request' => $last['request'] + 1, 'last_request' => 0, 'memory' => getum()]);
} else {
	$_ENV['table']->workers_stat->set($workerIdk, ['request' => 0, 'last_request' => 0, 'memory' => getum()]);
}

$serv->tick(10000, function ($id) use ($serv, $workerIdk) {
	$d = $serv->stats();
	$last = $_ENV['table']->workers_stat->get($workerIdk);
	if ($last != false) {
		$_ENV['table']->workers_stat->set($workerIdk, ['request' => $last['request'] + ($d['worker_request_count'] - $last['last_request']), 'last_request' => $d['worker_request_count'], 'memory' => getum()]);
	} else {
		$_ENV['table']->workers_stat->set($workerIdk, ['request' => 0, 'last_request' => $d['worker_request_count'], 'memory' => getum()]);
	}
});

if (__ISKF__ > 0) {
	$serv->tick(__ISKF__, function ($id) use ($serv) {
		$serv->reload();
	});
}
// hook tick_worker_before.php

if ($workerId == 0) {
	// hook tick_worker_0_start.php

	$this->http_server->tick(1000, function ($id) {
		$time = time();
		if ($time % 300 == 0) {
			$works = $requst = [];
			foreach ($_ENV['table']->workers_stat as $k => $row) {
				$_ENV['table']->workers_stat->del($k);
				$works[$k] = $row;
			}
			foreach ($_ENV['table']->request_stat as $k => $row) {
				$_ENV['table']->request_stat->del($k);
				$requst[$k] = $row;
			}

			foreach ($works as $k => $row) {
				stat_log([$k, $row['request'] < 0 ? $row['request'] * -1 : $row['request'], $row['last_request'], $row['memory']], 'workers');
			}

			foreach ($requst as $k => $row) {
				stat_log([$k, $row['count'], $row['ms']], 'api');
			}
		}
	});
	$this->http_server->tick(1000, function ($id) {
		$time = time();
		if ($time % 60 == 0) {
			$v = sys_getloadavg();
			stat_log($v, 'sys');
		}
	});
	$this->http_server->tick(60000, function ($id) {
		$time = time();
		foreach ($_ENV['table']->current as $k => $row) {
			$_time= $_ENV['table']->current->get($k,'time');
			if($time-$_time>600){
				$_ENV['table']->current->del($k);
			}
		}
	});

	// hook tick_worker_0_end.php

}
?>