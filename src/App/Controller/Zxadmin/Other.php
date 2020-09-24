<?php

namespace Zxadmin;

use Ctrl\AdminController;

/**
 * Class Other
 *
 * @module Admin
 * @name Other
 * @rank   99
 */
// hook zxadmin_other_use.php

Class Other extends AdminController
{

	// hook zxadmin_other_start.php

	/**
	 * @title  Stat_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Stat_PUT()
	{
		// hook zxadmin_other_stat_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Log_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Log_PUT()
	{
		// hook zxadmin_other_log_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Cache_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Cache_PUT()
	{
		// hook zxadmin_other_cache_put_start.php
		return $this->Template();
	}

	/**
	 * @title  Log_get
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Log_get()
	{
		// hook zxadmin_other_log_get_start.php
		$data = $this->AdminLog->GetList([]);
		// hook zxadmin_other_log_get_end.php
		$this->response('0000', ['data' => $data]);
	}

	/**
	 * @title  Cache_POST
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Cache_POST()
	{

		$tmp_clear = $this->request->param('tmp_clear', 0);
		$redis_clear = $this->request->param('redis_clear', 0);
		// hook zxadmin_other_cache_post_start.php
		if ($tmp_clear) {
			rmdir_recusive(__TMPDIR__);
			rmdir_recusive(__LOGDIR__);
			mkdir(__TMPDIR__, 0777, 1);
			mkdir(__LOGDIR__, 0777, 1);
			//$this->Stat->delete([]);
		}
		if ($redis_clear) {
			$this->User->CacheFlushAll();
		}
		$worker_reload = $this->request->param('worker_reload', 0);
		if ($worker_reload) {
			$this->http_server->reload($worker_reload == 1 ? true : false);
		}
		// hook zxadmin_other_cache_post_end.php
		$this->response('0000');

	}

	/**
	 * @title  Stat_Get
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Stat_Get()
	{
		$time = $this->request->param('time');
		$time = empty($time) ? date('Ymd') : date('Ymd', strtotime($time));
		$_list = read_stat_log($time, 'api');
		$_list = explode(PHP_EOL, $_list);
		$list = ['api_ms' => [], 'api_m_count' => [], 'api_count' => []];
		foreach ($_list as $v) {
			$v = trim($v);
			if (empty($v)) {
				continue;
			}
			$d = [];
			[$d['ymd'], $d['api'], $d['count'], $d['ms']] = explode("\t", $v);
			$t = explode(':', $d['ymd']);
			$list['api_ms'][$d['api']]['count'] += $d['count'];
			$list['api_ms'][$d['api']]['ms'] += $d['ms'];
			$list['api_ms'][$d['api']]['api'] = $d['api'];
			$list['api_count']['count'] += $d['count'];
			$list['api_count']['ms'] += $d['ms'];
			$list['api_m_count'][$t[0]]['count'] += $d['count'];
			$list['api_m_count'][$t[0]]['ms'] += $d['ms'];
			$list['api_m_count'][$t[0]]['h'] = $t[0] . ':00:00-' . $t[0] . ':59:59';

		}
		$list['api_ms'] = array_values($list['api_ms']);
		$list['api_m_count'] = array_values($list['api_m_count']);
		foreach ($list['api_ms'] as $k => $v) {
			$list['api_ms'][$k]['ms'] = bcdiv($v['ms'], 10000, 5);
			$list['api_ms'][$k]['msp'] = bcdiv($list['api_ms'][$k]['ms'], $v['count'], 5);
		}

		foreach ($list['api_m_count'] as $k => $v) {
			$list['api_m_count'][$k]['ms'] = bcdiv($v['ms'], 10000, 5);
			$list['api_m_count'][$k]['msp'] = bcdiv($list['api_m_count'][$k]['ms'], $v['count'], 5);
		}

		$_list = read_stat_log($time, 'workers');
		$_list = explode(PHP_EOL, $_list);
		foreach ($_list as $v) {
			$v = trim($v);
			if (empty($v)) {
				continue;
			}
			$d = [];
			[$d['ymd'], $id, $d['request_count'], $max, $d['memory'], $d['sys1'], $d['sys2'], $d['sys3']] = explode("\t", $v);
			$list['count'][$d['ymd']]['memory'] += $d['memory'];

			if (empty($list['worker'][$id])) {
				$list['worker'][$id]['type'] = 'line';
				$list['worker'][$id]['smooth'] = 'true';
				$list['worker'][$id]['name'] = sprintf('%04s#', $id);
			}
			$list['worker'][$id]['data'][] = $d['request_count'];
			$list['worker'][$id]['memory'][] = $d['memory'];
			!in_array($d['ymd'], $list['worker_data'], 1) AND $list['worker_data'][] = $d['ymd'];
		}
		$list['count'] = array_values($list['count']);
		$list['worker'] = array_values($list['worker']);


		$_list = read_stat_log($time, 'sys');
		$_list = explode(PHP_EOL, $_list);

		$list['sys'][1]['type'] = 'line';
		$list['sys'][1]['smooth'] = 'true';
		$list['sys'][1]['name'] = '01分';
		$list['sys'][2]['type'] = 'line';
		$list['sys'][2]['smooth'] = 'true';
		$list['sys'][2]['name'] = '05分';
		$list['sys'][3]['type'] = 'line';
		$list['sys'][3]['smooth'] = 'true';
		$list['sys'][3]['name'] = '15分';

		foreach ($_list as $v) {
			$v = trim($v);
			if (empty($v)) {
				continue;
			}
			$d = [];
			[$d['ymd'], $d['sys1'], $d['sys2'], $d['sys3']] = explode("\t", $v);

			$list['sys'][1]['data'][] = bcadd($d['sys1'], 0, 2);
			$list['sys'][2]['data'][] = bcadd($d['sys2'], 0, 2);
			$list['sys'][3]['data'][] = bcadd($d['sys3'], 0, 2);

			!in_array($d['ymd'], $list['sys_data'], 1) AND $list['sys_data'][] = $d['ymd'];
		}
		$list['count'] = array_values($list['count']);
		$list['worker'] = array_values($list['worker']);

		$list['sys'] = array_values($list['sys']);

		$this->response('0000', ['data' => $list]);
	}

	/**
	 * @title  Working
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Working()
	{

		$server = read_stat_log_day('workers');
		$data['server'] = [
			'min' => date('Y-m-d', strtotime(min($server) . ' 00:00:00')), 'max' => date('Y-m-d', strtotime(max($server) . ' 23:59:59')), 'today' => date('Y-m-d'),
		];
		$this->response('0000', ['data' => $data]);
	}


	/**
	 * @title  Table_PUT
	 * @auth   true
	 * @login  true
	 * @menu   false
	 * @button false
	 * @rank   99
	 * 2020/3/12 23:55
	 */
	public function action_Table_PUT()
	{
		return $this->Template();
	}

	// hook zxadmin_other_end.php
}

?>